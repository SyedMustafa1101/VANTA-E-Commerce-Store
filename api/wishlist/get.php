<?php

declare(strict_types=1);

define('VANTA_API', true);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';

json_response(['ok' => true, 'ids' => wishlist_service()->productIds(), 'authenticated' => current_user_id() !== null]);

