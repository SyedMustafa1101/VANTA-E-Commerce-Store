<?php

declare(strict_types=1);

final class CartService
{
    private const GUEST_KEY = 'vanta_cart';

    public function __construct(
        private ProductRepository $products,
        private CartRepository $carts
    ) {
    }

    /** @return array<int, array{variant_id:int,quantity:int}> */
    public function rawItems(): array
    {
        $userId = current_user_id();
        if ($userId !== null) {
            return $this->carts->itemsForUser($userId);
        }
        $items = [];
        foreach ((array) ($_SESSION[self::GUEST_KEY] ?? []) as $variantId => $quantity) {
            $items[] = ['variant_id' => (int) $variantId, 'quantity' => (int) $quantity];
        }
        return $items;
    }

    /** @return array<string, mixed> */
    public function addProduct(int $productId, int $quantity = 1): array
    {
        $variant = $this->products->firstAvailableVariant($productId);
        if ($variant === null) {
            throw new DomainException('This piece is currently unavailable.');
        }
        return $this->addVariant((int) $variant['id'], $quantity);
    }

    /** @return array<string, mixed> */
    public function addVariant(int $variantId, int $quantity = 1): array
    {
        $variant = $this->products->variantById($variantId);
        if ($variant === null || !$variant['is_active'] || !$variant['product_active']) {
            throw new DomainException('That product option is no longer available.');
        }
        $stock = (int) $variant['stock_quantity'];
        if ($stock < 1) {
            throw new DomainException('That size is out of stock.');
        }
        if ($quantity < 1 || $quantity > 10) {
            throw new DomainException('Choose a quantity between 1 and 10.');
        }
        $current = $this->quantityFor($variantId);
        $next = $current + $quantity;
        if ($next > $stock || $next > 10) {
            throw new DomainException('Only ' . $stock . ' of that option is available.');
        }
        $this->storeQuantity($variantId, $next);
        return $this->summary();
    }

    /** @return array<string, mixed> */
    public function update(int $variantId, string $action, ?int $quantity = null): array
    {
        $current = $this->quantityFor($variantId);
        if ($current < 1) {
            throw new DomainException('That bag item could not be found.');
        }
        if ($action === 'remove') {
            $this->storeQuantity($variantId, 0);
            return $this->summary();
        }
        $variant = $this->products->variantById($variantId);
        if ($variant === null || !$variant['is_active'] || !$variant['product_active']) {
            throw new DomainException('That product option is no longer available.');
        }
        $stock = (int) $variant['stock_quantity'];
        $next = $quantity !== null
            ? $quantity
            : ($action === 'increase' ? $current + 1 : $current - 1);
        if ($next < 1) {
            $this->storeQuantity($variantId, 0);
        } elseif ($next > $stock || $next > 10) {
            throw new DomainException('Only ' . $stock . ' of that option is available.');
        } else {
            $this->storeQuantity($variantId, $next);
        }
        return $this->summary();
    }

    /** @return array<string, mixed> */
    public function summary(): array
    {
        $lines = [];
        $subtotal = 0.0;
        $count = 0;
        foreach ($this->rawItems() as $item) {
            $variant = $this->products->variantById($item['variant_id']);
            if ($variant === null || !$variant['product_active']) {
                continue;
            }
            $quantity = max(1, (int) $item['quantity']);
            $unitPrice = (float) ($variant['sale_price'] ?? $variant['price']);
            $lineTotal = $unitPrice * $quantity;
            $lines[] = [
                'variant_id' => (int) $variant['id'],
                'product_id' => (int) $variant['product_id'],
                'name' => (string) $variant['name'],
                'slug' => (string) $variant['slug'],
                'url' => url('product.php?slug=' . rawurlencode((string) $variant['slug'])),
                'collection' => (string) $variant['collection'],
                'color' => (string) $variant['color_name'],
                'color_slug' => (string) $variant['color_slug'],
                'size' => (string) $variant['size'],
                'sku' => (string) $variant['sku'],
                'image' => media_url((string) $variant['image_path']),
                'quantity' => $quantity,
                'stock' => (int) $variant['stock_quantity'],
                'available' => $variant['is_active'] && (int) $variant['stock_quantity'] >= $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
            $subtotal += $lineTotal;
            $count += $quantity;
        }
        return ['lines' => $lines, 'subtotal' => $subtotal, 'count' => $count];
    }

    public function mergeGuestIntoUser(int $userId): void
    {
        $guest = (array) ($_SESSION[self::GUEST_KEY] ?? []);
        if ($guest === []) {
            return;
        }
        foreach ($guest as $variantId => $quantity) {
            $variant = $this->products->variantById((int) $variantId);
            if ($variant === null || !$variant['is_active'] || !$variant['product_active']) {
                continue;
            }
            $existing = 0;
            foreach ($this->carts->itemsForUser($userId) as $item) {
                if ($item['variant_id'] === (int) $variantId) {
                    $existing = $item['quantity'];
                    break;
                }
            }
            $next = min($existing + max(1, (int) $quantity), (int) $variant['stock_quantity'], 10);
            if ($next > 0) {
                $this->carts->setItem($userId, (int) $variantId, $next);
            }
        }
        unset($_SESSION[self::GUEST_KEY]);
    }

    public function clear(): void
    {
        $userId = current_user_id();
        if ($userId !== null) {
            $this->carts->clearForUser($userId);
        } else {
            unset($_SESSION[self::GUEST_KEY]);
        }
    }

    private function quantityFor(int $variantId): int
    {
        foreach ($this->rawItems() as $item) {
            if ($item['variant_id'] === $variantId) {
                return $item['quantity'];
            }
        }
        return 0;
    }

    private function storeQuantity(int $variantId, int $quantity): void
    {
        $userId = current_user_id();
        if ($userId !== null) {
            $this->carts->setItem($userId, $variantId, $quantity);
            return;
        }
        $cart = (array) ($_SESSION[self::GUEST_KEY] ?? []);
        if ($quantity < 1) {
            unset($cart[(string) $variantId]);
        } else {
            $cart[(string) $variantId] = $quantity;
        }
        $_SESSION[self::GUEST_KEY] = $cart;
    }
}
