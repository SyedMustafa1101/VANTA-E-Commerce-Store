<?php

declare(strict_types=1);

define('VANTA_API', true);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';

$input = require_post_json();
try {
    $quantity = filter_var($input['quantity'] ?? 1, FILTER_VALIDATE_INT);
    $quantity = $quantity === false ? 1 : (int) $quantity;
    if (!empty($input['variant_id'])) {
        $cart = cart_service()->addVariant((int) $input['variant_id'], $quantity);
    } else {
        $cart = cart_service()->addProduct((int) ($input['product_id'] ?? 0), $quantity);
    }
    json_response(['ok' => true, 'message' => 'Added to your bag.', 'cart' => $cart]);
} catch (DomainException $exception) {
    json_response(['ok' => false, 'message' => $exception->getMessage(), 'cart' => cart_service()->summary()], 422);
}

