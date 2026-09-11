<?php

declare(strict_types=1);

final class ReviewRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function approvedForProduct(int $productId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.rating, r.content, r.created_at, u.first_name, u.last_name
             FROM reviews r JOIN users u ON u.id = r.user_id
             WHERE r.product_id = ? AND r.status = \'approved\'
             ORDER BY r.created_at DESC'
        );
        $statement->execute([$productId]);
        return $statement->fetchAll();
    }

    public function hasPurchased(int $userId, int $productId): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM orders o
             JOIN order_items oi ON oi.order_id = o.id
             WHERE o.user_id = ? AND oi.product_id = ? AND o.order_status <> \'cancelled\'
             LIMIT 1'
        );
        $statement->execute([$userId, $productId]);
        return $statement->fetchColumn() !== false;
    }

    public function create(int $userId, int $productId, int $rating, string $content): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO reviews (user_id, product_id, rating, content, status)
             VALUES (?, ?, ?, ?, \'pending\')'
        );
        $statement->execute([$userId, $productId, $rating, $content]);
    }
}
