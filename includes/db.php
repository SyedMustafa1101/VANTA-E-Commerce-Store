<?php

declare(strict_types=1);

final class DatabaseUnavailableException extends RuntimeException
{
}

function db(): PDO
{
    static $pdo;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $path = dirname(__DIR__) . '/config/database.php';
    if (!is_file($path)) {
        throw new DatabaseUnavailableException(
            'Database configuration is missing. Copy config/database.example.php to config/database.php.'
        );
    }

    $database = require $path;
    if (!is_array($database)) {
        throw new DatabaseUnavailableException('Database configuration is invalid.');
    }

    foreach (['host', 'port', 'database', 'username', 'password', 'charset'] as $key) {
        if (!array_key_exists($key, $database)) {
            throw new DatabaseUnavailableException('Database configuration is incomplete.');
        }
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        (string) $database['host'],
        (int) $database['port'],
        (string) $database['database'],
        (string) $database['charset']
    );

    try {
        $pdo = new PDO($dsn, (string) $database['username'], (string) $database['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        throw new DatabaseUnavailableException(
            'VANTA could not connect to MySQL. Check config/database.php and import database/vanta.sql.',
            0,
            $exception
        );
    }

    return $pdo;
}

function setting(string $key, string $default = ''): string
{
    static $settings;

    if (!is_array($settings)) {
        $settings = [];
        $statement = db()->query('SELECT setting_key, setting_value FROM settings');
        foreach ($statement->fetchAll() as $row) {
            $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
        }
    }

    return $settings[$key] ?? $default;
}

