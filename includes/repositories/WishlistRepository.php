<?php

declare(strict_types=1);

final class WishlistRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, int> */
    public function productIds(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT wi.product_id FROM wishlists w
             JOIN wishlist_items wi ON wi.wishlist_id = w.id
             WHERE w.user_id = ? ORDER BY wi.created_at DESC'
        );
        $statement->execute([$userId]);
        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function add(int $userId, int $productId): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO wishlists (user_id) VALUES (?)
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
        );
        $statement->execute([$userId]);
        $wishlistId = (int) $this->pdo->lastInsertId();
        $item = $this->pdo->prepare(
            'INSERT IGNORE INTO wishlist_items (wishlist_id, product_id) VALUES (?, ?)'
        );
        $item->execute([$wishlistId, $productId]);
    }

    public function remove(int $userId, int $productId): void
    {
        $statement = $this->pdo->prepare(
            'DELETE wi FROM wishlist_items wi
             JOIN wishlists w ON w.id = wi.wishlist_id
             WHERE w.user_id = ? AND wi.product_id = ?'
        );
        $statement->execute([$userId, $productId]);
    }
}
