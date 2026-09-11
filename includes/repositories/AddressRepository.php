<?php

declare(strict_types=1);

final class AddressRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function forUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC'
        );
        $statement->execute([$userId]);
        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function ownedBy(int $addressId, int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM addresses WHERE id = ? AND user_id = ? LIMIT 1'
        );
        $statement->execute([$addressId, $userId]);
        $address = $statement->fetch();
        return $address ?: null;
    }

    /** @param array<string, string|int> $data */
    public function save(int $userId, array $data, ?int $addressId = null): int
    {
        $this->pdo->beginTransaction();
        try {
            if ((int) $data['is_default'] === 1) {
                $statement = $this->pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?');
                $statement->execute([$userId]);
            }

            if ($addressId === null) {
                $statement = $this->pdo->prepare(
                    'INSERT INTO addresses
                     (user_id, label, recipient_name, phone, address_line_1, address_line_2,
                      city, province, postal_code, country, is_default)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $statement->execute([
                    $userId, $data['label'], $data['recipient_name'], $data['phone'],
                    $data['address_line_1'], $data['address_line_2'] ?: null, $data['city'],
                    $data['province'], $data['postal_code'] ?: null, $data['country'],
                    $data['is_default'],
                ]);
                $addressId = (int) $this->pdo->lastInsertId();
            } else {
                $statement = $this->pdo->prepare(
                    'UPDATE addresses SET label = ?, recipient_name = ?, phone = ?,
                     address_line_1 = ?, address_line_2 = ?, city = ?, province = ?,
                     postal_code = ?, country = ?, is_default = ?
                     WHERE id = ? AND user_id = ?'
                );
                $statement->execute([
                    $data['label'], $data['recipient_name'], $data['phone'],
                    $data['address_line_1'], $data['address_line_2'] ?: null, $data['city'],
                    $data['province'], $data['postal_code'] ?: null, $data['country'],
                    $data['is_default'], $addressId, $userId,
                ]);
                if ($statement->rowCount() < 1 && $this->ownedBy($addressId, $userId) === null) {
                    throw new RuntimeException('Address not found.');
                }
            }

            $countStatement = $this->pdo->prepare('SELECT COUNT(*) FROM addresses WHERE user_id = ? AND is_default = 1');
            $countStatement->execute([$userId]);
            if ((int) $countStatement->fetchColumn() === 0) {
                $defaultStatement = $this->pdo->prepare(
                    'UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?'
                );
                $defaultStatement->execute([$addressId, $userId]);
            }

            $this->pdo->commit();
            return $addressId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function deleteOwned(int $addressId, int $userId): bool
    {
        $owned = $this->ownedBy($addressId, $userId);
        if ($owned === null) {
            return false;
        }
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare('DELETE FROM addresses WHERE id = ? AND user_id = ?');
            $statement->execute([$addressId, $userId]);
            if ((bool) $owned['is_default']) {
                $promote = $this->pdo->prepare(
                    'UPDATE addresses SET is_default = 1
                     WHERE user_id = ? ORDER BY id DESC LIMIT 1'
                );
                $promote->execute([$userId]);
            }
            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function setDefaultOwned(int $addressId, int $userId): bool
    {
        if ($this->ownedBy($addressId, $userId) === null) {
            return false;
        }
        $this->pdo->beginTransaction();
        try {
            $clear = $this->pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?');
            $clear->execute([$userId]);
            $set = $this->pdo->prepare('UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?');
            $set->execute([$addressId, $userId]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }
}
