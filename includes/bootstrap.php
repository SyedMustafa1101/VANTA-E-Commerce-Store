<?php

declare(strict_types=1);

$appConfig = require dirname(__DIR__) . '/config/app.php';

date_default_timezone_set((string) ($appConfig['timezone'] ?? 'UTC'));

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $cookiePath = rtrim((string) ($appConfig['base_url'] ?? ''), '/') . '/';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookiePath,
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';
$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}
require_once __DIR__ . '/repositories/ProductRepository.php';
require_once __DIR__ . '/repositories/UserRepository.php';
require_once __DIR__ . '/repositories/AddressRepository.php';
require_once __DIR__ . '/repositories/CartRepository.php';
require_once __DIR__ . '/repositories/WishlistRepository.php';
require_once __DIR__ . '/repositories/CouponRepository.php';
require_once __DIR__ . '/repositories/OrderRepository.php';
require_once __DIR__ . '/repositories/ReviewRepository.php';
require_once __DIR__ . '/repositories/NewsletterRepository.php';
require_once __DIR__ . '/mail/MailTransportInterface.php';
require_once __DIR__ . '/mail/SmtpMailTransport.php';
require_once __DIR__ . '/mail/OrderConfirmationEmailBuilder.php';
require_once __DIR__ . '/services/CartService.php';
require_once __DIR__ . '/services/WishlistService.php';
require_once __DIR__ . '/services/CouponService.php';
require_once __DIR__ . '/services/AuthService.php';
require_once __DIR__ . '/services/MailService.php';
require_once __DIR__ . '/services/OrderService.php';
require_once __DIR__ . '/catalog.php';
require_once __DIR__ . '/product-card.php';

set_exception_handler(static function (Throwable $exception): void {
    error_log(sprintf(
        'VANTA uncaught %s: %s in %s:%d',
        $exception::class,
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    ));
    if ($exception instanceof DatabaseUnavailableException) {
        render_database_unavailable($exception);
    }
    if (defined('VANTA_API') && VANTA_API === true) {
        json_response(['ok' => false, 'message' => 'The request could not be completed.'], 500);
    }
    http_response_code(500);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Something went wrong — VANTA</title><link rel="stylesheet" href="' . e(asset('css/app.css')) . '"></head>';
    echo '<body><main class="system-state"><div><p class="eyebrow">VANTA / ERROR</p><h1>Something shifted.</h1>';
    echo '<p>The request could not be completed. Please return to the store and try again.</p>';
    echo '<a class="button button--lime" href="' . e(url('index.php')) . '">Return home</a></div></main></body></html>';
});

try {
    db();
} catch (DatabaseUnavailableException $exception) {
    render_database_unavailable($exception);
}

if (!isset($_SESSION['rotated_at']) || time() - (int) $_SESSION['rotated_at'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['rotated_at'] = time();
}
