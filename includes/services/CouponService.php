<?php

declare(strict_types=1);

final class CouponService
{
    public function __construct(private CouponRepository $coupons)
    {
    }

    /** @return array{coupon:array<string,mixed>,discount:float} */
    public function validate(string $code, float $subtotal, ?int $userId = null, string $email = '', bool $forUpdate = false): array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            throw new DomainException('Enter a coupon code.');
        }
        $coupon = $this->coupons->findByCode($code, $forUpdate);
        $now = new DateTimeImmutable();
        if ($coupon === null || !(bool) $coupon['is_active']) {
            throw new DomainException('That coupon is not valid.');
        }
        if ($coupon['starts_at'] && new DateTimeImmutable((string) $coupon['starts_at']) > $now) {
            throw new DomainException('That coupon is not active yet.');
        }
        if ($coupon['expires_at'] && new DateTimeImmutable((string) $coupon['expires_at']) < $now) {
            throw new DomainException('That coupon has expired.');
        }
        if ($subtotal < (float) $coupon['minimum_order']) {
            throw new DomainException('This coupon requires a minimum order of ' . format_pkr((float) $coupon['minimum_order']) . '.');
        }
        $usage = $this->coupons->usageCount((int) $coupon['id'], $userId, normalize_email($email));
        if ($coupon['usage_limit'] !== null && $usage['total'] >= (int) $coupon['usage_limit']) {
            throw new DomainException('That coupon has reached its usage limit.');
        }
        if ($coupon['per_customer_limit'] !== null && $email !== '' && $usage['customer'] >= (int) $coupon['per_customer_limit']) {
            throw new DomainException('This coupon has already been used the maximum number of times.');
        }
        $discount = $coupon['type'] === 'percentage'
            ? $subtotal * min((float) $coupon['value'], 100) / 100
            : min((float) $coupon['value'], $subtotal);
        return ['coupon' => $coupon, 'discount' => max(0.0, min($discount, $subtotal))];
    }
}
