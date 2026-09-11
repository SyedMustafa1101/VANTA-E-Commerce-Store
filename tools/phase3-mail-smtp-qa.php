<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

$order = [
    'order_number' => 'VNT-2026-999999',
    'customer_first_name' => 'SMTP',
    'customer_last_name' => 'Tester',
    'customer_email' => 'customer@example.test',
    'placed_at' => '2026-09-11 12:00:00',
    'shipping_recipient' => 'SMTP Tester',
    'shipping_address_line_1' => '10 Test Avenue',
    'shipping_address_line_2' => '',
    'shipping_city' => 'Karachi',
    'shipping_province' => 'Sindh',
    'shipping_postal_code' => '74000',
    'shipping_country' => 'Pakistan',
    'payment_method' => 'demo_card',
    'payment_status' => 'paid',
    'order_status' => 'processing',
    'subtotal' => 6800,
    'discount_total' => 680,
    'shipping_total' => 300,
    'total' => 6420,
    'coupon_code' => 'VANTA10',
    'items' => [[
        'product_name' => 'VANTA Oversized Essential Tee',
        'variant_description' => 'Black / M',
        'sku' => 'VAN-ESS-BLK-M',
        'unit_price' => 6800,
        'quantity' => 1,
        'line_total' => 6800,
    ]],
];

$transport = new SmtpMailTransport([
    'enabled' => true,
    'host' => '127.0.0.1',
    'port' => 1025,
    'encryption' => 'none',
    'auth' => false,
    'username' => '',
    'password' => '',
    'from_email' => 'orders@vanta.test',
    'from_name' => 'VANTA',
    'reply_to_email' => '',
    'reply_to_name' => '',
    'timeout' => 5,
]);
$transport->send((new OrderConfirmationEmailBuilder())->build($order));
echo 'PHPMailer SMTP transport sent HTML and plain-text alternatives.' . PHP_EOL;
