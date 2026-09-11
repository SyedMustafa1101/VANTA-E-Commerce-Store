<?php

declare(strict_types=1);

define('VANTA_API', true);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';

$input = require_post_json();
try {
    $result = wishlist_service()->toggle((int) ($input['product_id'] ?? 0));
    json_response(['ok' => true] + $result);
} catch (DomainException $exception) {
    json_response(['ok' => false, 'message' => $exception->getMessage()], 422);
}

