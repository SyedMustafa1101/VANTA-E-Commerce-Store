<?php

declare(strict_types=1);

final class ProductRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $rows = $this->pdo->query($this->baseSelect() . ' WHERE p.is_active = 1 ORDER BY p.id')->fetchAll();
        return $this->hydrate($rows);
    }

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        $statement = $this->pdo->prepare($this->baseSelect() . ' WHERE p.slug = ? AND p.is_active = 1 LIMIT 1');
        $statement->execute([$slug]);
        $rows = $statement->fetchAll();
        return $rows === [] ? null : $this->hydrate($rows)[0];
    }

    /** @return array<int, array<string, mixed>> */
    public function forCollection(string $name): array
    {
        $statement = $this->pdo->prepare($this->baseSelect() . ' WHERE c.name = ? AND p.is_active = 1 ORDER BY p.id');
        $statement->execute([$name]);
        return $this->hydrate($statement->fetchAll());
    }

    /** @return array<int, array<string, mixed>> */
    public function related(int $productId, int $limit = 4): array
    {
        $statement = $this->pdo->prepare(
            $this->baseSelect()
            . ' JOIN product_relations pr ON pr.related_product_id = p.id'
            . ' WHERE pr.product_id = ? AND p.is_active = 1'
            . ' ORDER BY pr.sort_order LIMIT ' . max(1, min($limit, 12))
        );
        $statement->execute([$productId]);
        return $this->hydrate($statement->fetchAll());
    }

    /** @return array<string, array<string, mixed>> */
    public function collections(): array
    {
        $rows = $this->pdo->query(
            'SELECT id, name, slug, eyebrow, description, primary_image, secondary_image
             FROM collections ORDER BY sort_order, id'
        )->fetchAll();
        $collections = [];
        foreach ($rows as $row) {
            $collections[(string) $row['name']] = [
                'id' => (int) $row['id'],
                'slug' => (string) $row['slug'],
                'title' => (string) $row['name'],
                'eyebrow' => (string) $row['eyebrow'],
                'copy' => (string) $row['description'],
                'primary_image' => (string) $row['primary_image'],
                'secondary_image' => (string) $row['secondary_image'],
            ];
        }
        return $collections;
    }

    /** @return array<string, mixed>|null */
    public function collectionBySlug(string $slug): ?array
    {
        foreach ($this->collections() as $collection) {
            if ($collection['slug'] === $slug) {
                return $collection;
            }
        }
        return null;
    }

    /** @return array<int, string> */
    public function categories(): array
    {
        return array_map(
            static fn (array $row): string => (string) $row['name'],
            $this->pdo->query('SELECT name FROM categories ORDER BY id')->fetchAll()
        );
    }

    /** @return array<string, mixed>|null */
    public function variantById(int $variantId, bool $forUpdate = false): ?array
    {
        $sql = 'SELECT v.id, v.product_id, v.color_slug, v.color_name, v.color_hex, v.size, v.sku,
                       v.stock_quantity, v.is_active, p.name, p.slug, p.price, p.sale_price,
                       p.is_active AS product_active, c.name AS collection_name,
                       COALESCE(
                         (SELECT pi.path FROM product_images pi
                          WHERE pi.product_id = p.id AND pi.color_slug = v.color_slug
                          ORDER BY pi.sort_order LIMIT 1),
                         (SELECT pi.path FROM product_images pi
                          WHERE pi.product_id = p.id AND pi.color_slug = \'\'
                          ORDER BY pi.sort_order LIMIT 1)
                       ) AS image_path
                FROM product_variants v
                JOIN products p ON p.id = v.product_id
                JOIN collections c ON c.id = p.collection_id
                WHERE v.id = ? LIMIT 1';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$variantId]);
        $row = $statement->fetch();
        if (!$row) {
            return null;
        }
        return $this->normalizeVariant($row);
    }

    /** @return array<string, mixed>|null */
    public function findVariant(int $productId, string $color, string $size): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id FROM product_variants
             WHERE product_id = ? AND color_slug = ? AND size = ? AND is_active = 1 LIMIT 1'
        );
        $statement->execute([$productId, $color, $size]);
        $id = $statement->fetchColumn();
        return $id === false ? null : $this->variantById((int) $id);
    }

    /** @return array<string, mixed>|null */
    public function firstAvailableVariant(int $productId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id FROM product_variants
             WHERE product_id = ? AND is_active = 1 AND stock_quantity > 0
             ORDER BY id LIMIT 1'
        );
        $statement->execute([$productId]);
        $id = $statement->fetchColumn();
        return $id === false ? null : $this->variantById((int) $id);
    }

    private function baseSelect(): string
    {
        return 'SELECT p.*, cat.name AS category_name, c.name AS collection_name
                FROM products p
                JOIN categories cat ON cat.id = p.category_id
                JOIN collections c ON c.id = p.collection_id';
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function hydrate(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $marks = implode(',', array_fill(0, count($ids), '?'));

        $imageStatement = $this->pdo->prepare(
            'SELECT product_id, color_slug, path FROM product_images
             WHERE product_id IN (' . $marks . ') ORDER BY product_id, color_slug, sort_order'
        );
        $imageStatement->execute($ids);
        $baseImages = [];
        $colorImages = [];
        foreach ($imageStatement->fetchAll() as $image) {
            $productId = (int) $image['product_id'];
            $color = (string) $image['color_slug'];
            if ($color === '') {
                $baseImages[$productId][] = (string) $image['path'];
            } else {
                $colorImages[$productId][$color][] = (string) $image['path'];
            }
        }

        $variantStatement = $this->pdo->prepare(
            'SELECT id, product_id, color_slug, color_name, color_hex, size, sku, stock_quantity, is_active
             FROM product_variants WHERE product_id IN (' . $marks . ')
             ORDER BY product_id, id'
        );
        $variantStatement->execute($ids);
        $colors = [];
        $stock = [];
        $variants = [];
        foreach ($variantStatement->fetchAll() as $variant) {
            $productId = (int) $variant['product_id'];
            $color = (string) $variant['color_slug'];
            if (!isset($colors[$productId][$color])) {
                $colors[$productId][$color] = [
                    'id' => $color,
                    'name' => (string) $variant['color_name'],
                    'hex' => (string) $variant['color_hex'],
                    'images' => $colorImages[$productId][$color] ?? $baseImages[$productId] ?? [],
                ];
            }
            $size = (string) $variant['size'];
            $quantity = (int) $variant['stock_quantity'];
            $stock[$productId][$color][$size] = $quantity;
            $variants[$productId][$color][$size] = [
                'id' => (int) $variant['id'],
                'sku' => (string) $variant['sku'],
                'stock' => $quantity,
                'active' => (bool) $variant['is_active'],
            ];
        }

        return array_map(static function (array $row) use ($baseImages, $colors, $stock, $variants): array {
            $id = (int) $row['id'];
            $keywords = json_decode((string) ($row['keywords'] ?? '[]'), true);
            return [
                'id' => $id,
                'slug' => (string) $row['slug'],
                'name' => (string) $row['name'],
                'category' => (string) $row['category_name'],
                'collection' => (string) $row['collection_name'],
                'price' => (int) round((float) $row['price']),
                'sale_price' => $row['sale_price'] === null ? null : (int) round((float) $row['sale_price']),
                'label' => $row['label'] === null ? null : (string) $row['label'],
                'popularity' => (int) $row['popularity'],
                'description' => (string) $row['description'],
                'keywords' => is_array($keywords) ? array_values($keywords) : [],
                'images' => $baseImages[$id] ?? [],
                'colors' => array_values($colors[$id] ?? []),
                'stock' => $stock[$id] ?? [],
                'variants' => $variants[$id] ?? [],
                'materials' => (string) $row['materials'],
                'care' => (string) $row['care'],
            ];
        }, $rows);
    }

    /** @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    private function normalizeVariant(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'product_id' => (int) $row['product_id'],
            'color_slug' => (string) $row['color_slug'],
            'color_name' => (string) $row['color_name'],
            'color_hex' => (string) $row['color_hex'],
            'size' => (string) $row['size'],
            'sku' => (string) $row['sku'],
            'stock_quantity' => (int) $row['stock_quantity'],
            'is_active' => (bool) $row['is_active'],
            'product_active' => (bool) $row['product_active'],
            'name' => (string) $row['name'],
            'slug' => (string) $row['slug'],
            'price' => (float) $row['price'],
            'sale_price' => $row['sale_price'] === null ? null : (float) $row['sale_price'],
            'collection' => (string) $row['collection_name'],
            'image_path' => (string) ($row['image_path'] ?? ''),
        ];
    }
}
