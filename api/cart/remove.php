<?php

declare(strict_types=1);

define('VANTA_API', true);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';

$input = require_post_json();
try {
    $cart = cart_service()->update((int) ($input['variant_id'] ?? 0), 'remove');
    json_response(['ok' => true, 'cart' => $cart]);
} catch (DomainException $exception) {
    json_response(['ok' => false, 'message' => $exception->getMessage()], 422);
}

