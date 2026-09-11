<?php

declare(strict_types=1);

final class WishlistService
{
    private const GUEST_KEY = 'vanta_wishlist';

    public function __construct(
        private PDO $pdo,
        private WishlistRepository $wishlists
    ) {
    }

    /** @return array<int, int> */
    public function productIds(): array
    {
        $userId = current_user_id();
        if ($userId !== null) {
            return $this->wishlists->productIds($userId);
        }
        return array_values(array_unique(array_map('intval', (array) ($_SESSION[self::GUEST_KEY] ?? []))));
    }

    /** @return array{ids:array<int,int>,added:bool} */
    public function toggle(int $productId): array
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM products WHERE id = ? AND is_active = 1');
        $statement->execute([$productId]);
        if ($statement->fetchColumn() === false) {
            throw new DomainException('That product could not be found.');
        }
        $ids = $this->productIds();
        $added = !in_array($productId, $ids, true);
        $userId = current_user_id();
        if ($userId !== null) {
            $added ? $this->wishlists->add($userId, $productId) : $this->wishlists->remove($userId, $productId);
        } else {
            if ($added) {
                $ids[] = $productId;
            } else {
                $ids = array_values(array_filter($ids, static fn (int $id): bool => $id !== $productId));
            }
            $_SESSION[self::GUEST_KEY] = $ids;
        }
        return ['ids' => $this->productIds(), 'added' => $added];
    }

    public function mergeGuestIntoUser(int $userId): void
    {
        foreach (array_map('intval', (array) ($_SESSION[self::GUEST_KEY] ?? [])) as $productId) {
            $this->wishlists->add($userId, $productId);
        }
        unset($_SESSION[self::GUEST_KEY]);
    }
}
