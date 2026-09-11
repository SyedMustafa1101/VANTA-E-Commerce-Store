<?php

declare(strict_types=1);

/** @var array<string, mixed> $order */
/** @var string $customerName */
/** @var string $orderDate */
/** @var Closure $formatMoney */
/** @var Closure $formatStatus */
/** @var Closure $escape */

$address = array_values(array_filter([
    $order['shipping_recipient'] ?? '',
    $order['shipping_address_line_1'] ?? '',
    $order['shipping_address_line_2'] ?? '',
    trim((string) ($order['shipping_city'] ?? '') . ', ' . (string) ($order['shipping_province'] ?? '') . ' ' . (string) ($order['shipping_postal_code'] ?? '')),
    $order['shipping_country'] ?? '',
], static fn (mixed $line): bool => trim((string) $line) !== ''));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $escape('Order ' . $order['order_number'] . ' confirmed') ?></title>
    <style>
        @media only screen and (max-width: 620px) {
            .email-shell { width: 100% !important; }
            .email-pad { padding-left: 22px !important; padding-right: 22px !important; }
            .item-price { display: block !important; width: 100% !important; padding-top: 8px !important; text-align: left !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#090909;color:#111111;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#090909;">
    <tr>
        <td align="center" style="padding:32px 12px;">
            <table role="presentation" class="email-shell" width="620" cellspacing="0" cellpadding="0" border="0" style="width:620px;max-width:620px;background:#f4f1e8;">
                <tr>
                    <td class="email-pad" style="padding:30px 42px;background:#111111;color:#f4f1e8;border-bottom:4px solid #c8ff00;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="font-size:25px;font-weight:800;letter-spacing:7px;">VANTA</td>
                                <td align="right" style="font-size:11px;letter-spacing:2px;color:#c8ff00;">ORDER CONFIRMED</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="email-pad" style="padding:42px 42px 24px;">
                        <p style="margin:0 0 12px;font-size:12px;font-weight:700;letter-spacing:2px;color:#667000;">AFTER DARK / DISPATCH</p>
                        <h1 style="margin:0 0 18px;font-size:32px;line-height:1.08;color:#111111;">The night is yours.</h1>
                        <p style="margin:0;font-size:16px;line-height:1.6;color:#333333;">Hi <?= $escape($customerName) ?>, your order has been placed successfully. We will keep you updated as it moves through the dark.</p>
                    </td>
                </tr>
                <tr>
                    <td class="email-pad" style="padding:0 42px 30px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-top:1px solid #cbc7bc;border-bottom:1px solid #cbc7bc;">
                            <tr>
                                <td style="padding:18px 0;font-size:12px;line-height:1.7;color:#555555;">
                                    ORDER NUMBER<br><strong style="font-size:15px;color:#111111;"><?= $escape($order['order_number']) ?></strong>
                                </td>
                                <td align="right" style="padding:18px 0;font-size:12px;line-height:1.7;color:#555555;">
                                    ORDER DATE<br><strong style="font-size:15px;color:#111111;"><?= $escape($orderDate) ?></strong>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="email-pad" style="padding:0 42px 12px;">
                        <h2 style="margin:0;font-size:16px;letter-spacing:1px;color:#111111;">YOUR EDIT</h2>
                    </td>
                </tr>
                <?php foreach ((array) $order['items'] as $item): ?>
                    <tr>
                        <td class="email-pad" style="padding:0 42px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-bottom:1px solid #d8d4ca;">
                                <tr>
                                    <td style="padding:18px 0;">
                                        <strong style="display:block;font-size:15px;line-height:1.45;color:#111111;"><?= $escape($item['product_name']) ?></strong>
                                        <span style="display:block;margin-top:5px;font-size:12px;line-height:1.5;color:#666666;"><?= $escape($item['variant_description']) ?> · SKU <?= $escape($item['sku']) ?></span>
                                        <span style="display:block;margin-top:5px;font-size:12px;color:#666666;">Quantity <?= (int) $item['quantity'] ?> · <?= $escape($formatMoney($item['unit_price'])) ?> each</span>
                                    </td>
                                    <td class="item-price" align="right" style="padding:18px 0;font-size:14px;font-weight:700;color:#111111;"><?= $escape($formatMoney($item['line_total'])) ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td class="email-pad" style="padding:24px 42px 30px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr><td style="padding:5px 0;font-size:13px;color:#555555;">Subtotal</td><td align="right" style="padding:5px 0;font-size:13px;"><?= $escape($formatMoney($order['subtotal'])) ?></td></tr>
                            <tr><td style="padding:5px 0;font-size:13px;color:#555555;">Discount<?= !empty($order['coupon_code']) ? ' / ' . $escape($order['coupon_code']) : '' ?></td><td align="right" style="padding:5px 0;font-size:13px;">−<?= $escape($formatMoney($order['discount_total'])) ?></td></tr>
                            <tr><td style="padding:5px 0;font-size:13px;color:#555555;">Shipping</td><td align="right" style="padding:5px 0;font-size:13px;"><?= (float) $order['shipping_total'] > 0 ? $escape($formatMoney($order['shipping_total'])) : 'Free' ?></td></tr>
                            <tr><td style="padding:15px 0 5px;border-top:2px solid #111111;font-size:17px;font-weight:800;">Total</td><td align="right" style="padding:15px 0 5px;border-top:2px solid #111111;font-size:17px;font-weight:800;"><?= $escape($formatMoney($order['total'])) ?></td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="email-pad" style="padding:28px 42px;background:#e8e4da;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td valign="top" width="56%" style="padding-right:20px;">
                                    <p style="margin:0 0 10px;font-size:11px;font-weight:700;letter-spacing:1.5px;color:#667000;">SHIPPING ADDRESS</p>
                                    <p style="margin:0;font-size:13px;line-height:1.7;color:#222222;"><?= implode('<br>', array_map($escape, $address)) ?></p>
                                </td>
                                <td valign="top">
                                    <p style="margin:0 0 10px;font-size:11px;font-weight:700;letter-spacing:1.5px;color:#667000;">ORDER STATE</p>
                                    <p style="margin:0;font-size:13px;line-height:1.7;color:#222222;">
                                        <?= $escape($formatStatus((string) $order['payment_method'])) ?><br>
                                        Payment: <?= $escape($formatStatus((string) $order['payment_status'])) ?><br>
                                        Status: <?= $escape($formatStatus((string) $order['order_status'])) ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="email-pad" style="padding:28px 42px;background:#111111;color:#aaa69c;font-size:11px;line-height:1.7;text-align:center;">
                        VANTA · BUILT FOR AFTER DARK<br>
                        This transactional email confirms your order and contains no payment-card details.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
