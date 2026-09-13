<?php

declare(strict_types=1);

/** Escape output for safe HTML rendering. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Read a value from the central application configuration. */
function config(string $key, mixed $default = null): mixed
{
    /** @var array<string, mixed> $appConfig */
    global $appConfig;

    return $appConfig[$key] ?? $default;
}

/** Build a project-relative URL that works in an Apache subdirectory. */
function url(string $path = ''): string
{
    $base = (string) config('base_url', '');
    $cleanPath = ltrim($path, '/');

    return $base . ($cleanPath === '' ? '/' : '/' . $cleanPath);
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/** Resolve seeded assets and runtime uploads without accepting arbitrary URLs. */
function media_url(string $path): string
{
    $clean = str_replace('\\', '/', trim($path));
    if ($clean === '' || str_contains($clean, '..') || preg_match('/[\x00-\x1F\x7F]/', $clean)) {
        return '';
    }
    return str_starts_with($clean, 'uploads/') ? url($clean) : asset($clean);
}

function is_active_page(string $page, string $currentPage): bool
{
    return $page === $currentPage;
}

/** Render a small inline icon without adding an icon-library dependency. */
function icon(string $name, string $class = ''): string
{
    $icons = [
        'arrow-up-right' => '<path d="M7 17 17 7M8 7h9v9"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'bag' => '<path d="M6 8h12l1 12H5L6 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.7-7.5 1.1-1.1a5.5 5.5 0 0 0 0-7.8Z"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
    ];

    if (!isset($icons[$name])) {
        return '';
    }

    return sprintf(
        '<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="square" stroke-linejoin="miter" aria-hidden="true">%s</svg>',
        e($class),
        $icons[$name]
    );
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function request_csrf_token(): ?string
{
    $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (is_string($header) && $header !== '') {
        return $header;
    }
    return isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : null;
}

function normalize_email(string $email): string
{
    return mb_strtolower(trim($email), 'UTF-8');
}

function redirect(string $path, int $status = 303): void
{
    header('Location: ' . url($path), true, $status);
    exit;
}

function safe_return_path(?string $path, string $default = 'account/index.php'): string
{
    if (
        !is_string($path)
        || $path === ''
        || preg_match('/[\x00-\x1F\x7F]/', $path)
        || str_contains($path, '://')
        || str_starts_with($path, '//')
    ) {
        return $default;
    }
    $path = ltrim($path, '/');
    return str_contains($path, '..') ? $default : $path;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** @return array<int, array{type:string,message:string}> */
function pull_flashes(): array
{
    $messages = (array) ($_SESSION['flash'] ?? []);
    unset($_SESSION['flash']);
    return $messages;
}

/** @return array<string, mixed> */
function json_input(): array
{
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || $raw === '') {
        return $_POST;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/** @param array<string, mixed> $payload */
function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function require_post_json(): array
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
    }
    $input = json_input();
    $token = is_string($input['csrf_token'] ?? null) ? $input['csrf_token'] : request_csrf_token();
    if (!verify_csrf($token)) {
        json_response(['ok' => false, 'message' => 'Your session expired. Refresh and try again.'], 419);
    }
    return $input;
}

/** @return array<string, mixed>|null */
function current_user(): ?array
{
    global $vantaCurrentUser, $vantaCurrentUserLoaded;
    if ($vantaCurrentUserLoaded ?? false) {
        return is_array($vantaCurrentUser) ? $vantaCurrentUser : null;
    }
    $vantaCurrentUserLoaded = true;
    $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$userId) {
        $vantaCurrentUser = null;
        return null;
    }
    $vantaCurrentUser = (new UserRepository(db()))->findById((int) $userId);
    if ($vantaCurrentUser === null || $vantaCurrentUser['status'] !== 'active') {
        unset($_SESSION['user_id']);
        return null;
    }
    return $vantaCurrentUser;
}

function reset_current_user_cache(): void
{
    global $vantaCurrentUser, $vantaCurrentUserLoaded;
    $vantaCurrentUser = null;
    $vantaCurrentUserLoaded = false;
}

function current_user_id(): ?int
{
    $user = current_user();
    return $user === null ? null : (int) $user['id'];
}

function require_auth(): array
{
    $user = current_user();
    if ($user !== null) {
        return $user;
    }
    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $base = (string) config('base_url', '');
    if ($base !== '' && str_starts_with($requestUri, $base . '/')) {
        $requestUri = substr($requestUri, strlen($base) + 1);
    }
    flash('error', 'Sign in to continue.');
    redirect('login.php?return=' . rawurlencode(safe_return_path($requestUri)));
}

function cart_service(): CartService
{
    static $service;
    return $service ??= new CartService(product_repository(), new CartRepository(db()));
}

function wishlist_service(): WishlistService
{
    static $service;
    return $service ??= new WishlistService(db(), new WishlistRepository(db()));
}

function coupon_service(): CouponService
{
    static $service;
    return $service ??= new CouponService(new CouponRepository(db()));
}

/** @return array<string, mixed> */
function mail_config(): array
{
    static $mailConfig;
    if (is_array($mailConfig)) {
        return $mailConfig;
    }
    $path = dirname(__DIR__) . '/config/mail.php';
    if (!is_file($path)) {
        return $mailConfig = ['enabled' => false];
    }
    $loaded = require $path;
    return $mailConfig = is_array($loaded) ? $loaded : ['enabled' => false];
}

function mail_service(): MailService
{
    static $service;
    return $service ??= new MailService(
        new OrderRepository(db()),
        new SmtpMailTransport(mail_config()),
        new OrderConfirmationEmailBuilder()
    );
}

function order_service(): OrderService
{
    static $service;
    return $service ??= new OrderService(
        db(),
        product_repository(),
        cart_service(),
        coupon_service(),
        new CouponRepository(db()),
        new OrderRepository(db()),
        mail_service()
    );
}

function auth_service(): AuthService
{
    static $service;
    return $service ??= new AuthService(
        new UserRepository(db()),
        cart_service(),
        wishlist_service()
    );
}

function format_status(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

function render_database_unavailable(DatabaseUnavailableException $exception): void
{
    error_log('VANTA database unavailable: ' . $exception->getMessage());
    if (defined('VANTA_API') && VANTA_API === true) {
        json_response(['ok' => false, 'message' => 'The store database is temporarily unavailable.'], 503);
    }
    http_response_code(503);
    $message = config('environment') === 'development'
        ? $exception->getMessage()
        : 'The store is temporarily unavailable. Please try again shortly.';
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Store setup — VANTA</title><link rel="stylesheet" href="' . e(asset('css/app.css')) . '"></head>';
    echo '<body><main class="system-state"><div><p class="eyebrow">VANTA / DATABASE</p><h1>Commerce is offline.</h1>';
    echo '<p>' . e($message) . '</p><p>Import database/vanta.sql, then configure the ignored config/database.php file.</p></div></main></body></html>';
    exit;
}
