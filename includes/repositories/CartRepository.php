<?php

declare(strict_types=1);

final class CartRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function cartIdForUser(int $userId): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO carts (user_id) VALUES (?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
        );
        $statement->execute([$userId]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<int, array{variant_id:int,quantity:int}> */
    public function itemsForUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT ci.variant_id, ci.quantity
             FROM carts c JOIN cart_items ci ON ci.cart_id = c.id
             WHERE c.user_id = ? ORDER BY ci.created_at'
        );
        $statement->execute([$userId]);
        return array_map(static fn (array $row): array => [
            'variant_id' => (int) $row['variant_id'],
            'quantity' => (int) $row['quantity'],
        ], $statement->fetchAll());
    }

    public function setItem(int $userId, int $variantId, int $quantity): void
    {
        $cartId = $this->cartIdForUser($userId);
        if ($quantity < 1) {
            $statement = $this->pdo->prepare('DELETE FROM cart_items WHERE cart_id = ? AND variant_id = ?');
            $statement->execute([$cartId, $variantId]);
            return;
        }
        $statement = $this->pdo->prepare(
            'INSERT INTO cart_items (cart_id, variant_id, quantity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), updated_at = CURRENT_TIMESTAMP'
        );
        $statement->execute([$cartId, $variantId, $quantity]);
    }

    public function clearForUser(int $userId): void
    {
        $cartId = $this->cartIdForUser($userId);
        $statement = $this->pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?');
        $statement->execute([$cartId]);
    }
}
