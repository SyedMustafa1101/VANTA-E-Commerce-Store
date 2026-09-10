<?php

declare(strict_types=1);

$appConfig = require dirname(__DIR__) . '/config/app.php';

date_default_timezone_set((string) ($appConfig['timezone'] ?? 'UTC'));

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/catalog.php';
require_once __DIR__ . '/product-card.php';
