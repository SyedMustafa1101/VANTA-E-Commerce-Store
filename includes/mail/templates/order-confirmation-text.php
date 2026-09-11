<?php

declare(strict_types=1);

/** @var array<string, mixed> $order */
/** @var string $customerName */
/** @var string $orderDate */
/** @var Closure $formatMoney */
/** @var Closure $formatStatus */

$address = array_values(array_filter([
    $order['shipping_recipient'] ?? '',
    $order['shipping_address_line_1'] ?? '',
    $order['shipping_address_line_2'] ?? '',
    trim((string) ($order['shipping_city'] ?? '') . ', ' . (string) ($order['shipping_province'] ?? '') . ' ' . (string) ($order['shipping_postal_code'] ?? '')),
    $order['shipping_country'] ?? '',
], static fn (mixed $line): bool => trim((string) $line) !== ''));
?>
VANTA
BUILT FOR AFTER DARK

THE NIGHT IS YOURS

Hi <?= $customerName ?>,

Your order has been placed successfully.

Order number: <?= $order['order_number'] ?>
Order date: <?= $orderDate ?>

PURCHASED ITEMS
<?php foreach ((array) $order['items'] as $item): ?>

<?= $item['product_name'] ?>
<?= $item['variant_description'] ?> / SKU <?= $item['sku'] ?>
Quantity: <?= (int) $item['quantity'] ?>
Unit price: <?= $formatMoney($item['unit_price']) ?>
Line total: <?= $formatMoney($item['line_total']) ?>
<?php endforeach; ?>

Subtotal: <?= $formatMoney($order['subtotal']) ?>
Discount<?= !empty($order['coupon_code']) ? ' (' . $order['coupon_code'] . ')' : '' ?>: -<?= $formatMoney($order['discount_total']) ?>
Shipping: <?= (float) $order['shipping_total'] > 0 ? $formatMoney($order['shipping_total']) : 'Free' ?>
Total: <?= $formatMoney($order['total']) ?>

SHIPPING ADDRESS
<?= implode(PHP_EOL, $address) ?>

Payment method: <?= $formatStatus((string) $order['payment_method']) ?>
Payment status: <?= $formatStatus((string) $order['payment_status']) ?>
Order status: <?= $formatStatus((string) $order['order_status']) ?>

This transactional email contains no payment-card details.
