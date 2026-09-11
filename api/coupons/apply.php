<?php

declare(strict_types=1);

define('VANTA_API', true);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';

$input = require_post_json();
$code = strtoupper(trim((string) ($input['code'] ?? '')));
$user = current_user();
$email = $user === null ? normalize_email((string) ($input['email'] ?? '')) : (string) $user['email'];
try {
    $summary = cart_service()->summary();
    $result = coupon_service()->validate($code, (float) $summary['subtotal'], current_user_id(), $email);
    $_SESSION['coupon_code'] = $code;
    $quote = order_service()->quote($email);
    json_response([
        'ok' => true,
        'message' => $code . ' applied.',
        'quote' => $quote,
        'discount' => $result['discount'],
    ]);
} catch (DomainException $exception) {
    unset($_SESSION['coupon_code']);
    json_response(['ok' => false, 'message' => $exception->getMessage(), 'quote' => order_service()->quote($email)], 422);
}

