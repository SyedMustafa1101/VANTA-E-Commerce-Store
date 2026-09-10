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

