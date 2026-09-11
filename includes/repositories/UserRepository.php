<?php

declare(strict_types=1);

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, first_name, last_name, email, password_hash, status, created_at
             FROM users WHERE id = ? LIMIT 1'
        );
        $statement->execute([$id]);
        $user = $statement->fetch();
        return $user ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, first_name, last_name, email, password_hash, status, created_at
             FROM users WHERE email = ? LIMIT 1'
        );
        $statement->execute([$email]);
        $user = $statement->fetch();
        return $user ?: null;
    }

    public function create(string $firstName, string $lastName, string $email, string $passwordHash): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (first_name, last_name, email, password_hash)
             VALUES (?, ?, ?, ?)'
        );
        $statement->execute([$firstName, $lastName, $email, $passwordHash]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateProfile(int $id, string $firstName, string $lastName, string $email): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?'
        );
        $statement->execute([$firstName, $lastName, $email, $id]);
    }

    public function markLogin(int $id): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
        $statement->execute([$id]);
    }

    public function countRecentFailures(string $email, string $ipHash, int $minutes = 15): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE email = ? AND ip_hash = ? AND was_successful = 0
               AND attempted_at >= DATE_SUB(NOW(), INTERVAL ' . max(1, min($minutes, 60)) . ' MINUTE)'
        );
        $statement->execute([$email, $ipHash]);
        return (int) $statement->fetchColumn();
    }

    public function recordLoginAttempt(string $email, string $ipHash, bool $successful): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO login_attempts (email, ip_hash, attempted_at, was_successful)
             VALUES (?, ?, NOW(), ?)'
        );
        $statement->execute([$email, $ipHash, $successful ? 1 : 0]);
    }
}
