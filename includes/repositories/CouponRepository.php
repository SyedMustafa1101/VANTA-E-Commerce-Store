<?php

declare(strict_types=1);

final class CouponRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<string, mixed>|null */
    public function findByCode(string $code, bool $forUpdate = false): ?array
    {
        $sql = 'SELECT * FROM coupons WHERE code = ? LIMIT 1' . ($forUpdate ? ' FOR UPDATE' : '');
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$code]);
        $coupon = $statement->fetch();
        return $coupon ?: null;
    }

    public function usageCount(int $couponId, ?int $userId, string $email): array
    {
        $total = $this->pdo->prepare('SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = ?');
        $total->execute([$couponId]);
        $customer = $this->pdo->prepare(
            'SELECT COUNT(*) FROM coupon_usage
             WHERE coupon_id = ? AND ((user_id IS NOT NULL AND user_id = ?) OR customer_email = ?)'
        );
        $customer->execute([$couponId, $userId, $email]);
        return ['total' => (int) $total->fetchColumn(), 'customer' => (int) $customer->fetchColumn()];
    }

    public function recordUsage(int $couponId, ?int $userId, int $orderId, string $email): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO coupon_usage (coupon_id, user_id, order_id, customer_email, used_at)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $statement->execute([$couponId, $userId, $orderId, $email]);
    }
}
