<?php

declare(strict_types=1);

final class OrderRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @param array<string, mixed> $order */
    public function create(array $order): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO orders (
                user_id, coupon_id, order_number, customer_first_name, customer_last_name,
                customer_email, customer_phone, shipping_label, shipping_recipient,
                shipping_address_line_1, shipping_address_line_2, shipping_city,
                shipping_province, shipping_postal_code, shipping_country, shipping_method,
                payment_method, payment_status, order_status, subtotal, discount_total,
                shipping_total, total, coupon_code, placed_at
             ) VALUES (
                :user_id, :coupon_id, :order_number, :customer_first_name, :customer_last_name,
                :customer_email, :customer_phone, :shipping_label, :shipping_recipient,
                :shipping_address_line_1, :shipping_address_line_2, :shipping_city,
                :shipping_province, :shipping_postal_code, :shipping_country, :shipping_method,
                :payment_method, :payment_status, :order_status, :subtotal, :discount_total,
                :shipping_total, :total, :coupon_code, NOW()
             )'
        );
        $statement->execute($order);
        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $item */
    public function addItem(int $orderId, array $item): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO order_items (
                order_id, product_id, variant_id, product_name, variant_description,
                sku, image_path, unit_price, quantity, line_total
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $orderId, $item['product_id'], $item['variant_id'], $item['name'],
            $item['variant_description'], $item['sku'], $item['image_path'] ?: null,
            $item['unit_price'], $item['quantity'], $item['line_total'],
        ]);
    }

    public function queueConfirmationEmail(int $orderId, string $recipientEmail): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO order_email_deliveries (order_id, email_type, recipient_email, status)
             VALUES (?, \'order_confirmation\', ?, \'pending\')
             ON DUPLICATE KEY UPDATE recipient_email = VALUES(recipient_email)'
        );
        $statement->execute([$orderId, normalize_email($recipientEmail)]);
    }

    public function claimConfirmationEmail(int $orderId): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE order_email_deliveries
             SET status = \'sending\', attempt_count = attempt_count + 1, last_attempted_at = NOW()
             WHERE order_id = ? AND email_type = \'order_confirmation\' AND status = \'pending\''
        );
        $statement->execute([$orderId]);
        return $statement->rowCount() === 1;
    }

    public function markConfirmationEmailSent(int $orderId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE order_email_deliveries
             SET status = \'sent\', sent_at = NOW()
             WHERE order_id = ? AND email_type = \'order_confirmation\' AND status = \'sending\''
        );
        $statement->execute([$orderId]);
    }

    public function markConfirmationEmailFailed(int $orderId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE order_email_deliveries
             SET status = \'failed\'
             WHERE order_id = ? AND email_type = \'order_confirmation\' AND status = \'sending\''
        );
        $statement->execute([$orderId]);
    }

    public function confirmationEmailStatus(int $orderId): ?string
    {
        $statement = $this->pdo->prepare(
            'SELECT status FROM order_email_deliveries
             WHERE order_id = ? AND email_type = \'order_confirmation\' LIMIT 1'
        );
        $statement->execute([$orderId]);
        $status = $statement->fetchColumn();
        return $status === false ? null : (string) $status;
    }

    public function decrementStock(int $variantId, int $quantity): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE product_variants
             SET stock_quantity = stock_quantity - ?
             WHERE id = ? AND is_active = 1 AND stock_quantity >= ?'
        );
        $statement->execute([$quantity, $variantId, $quantity]);
        return $statement->rowCount() === 1;
    }

    public function orderNumberExists(string $orderNumber): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM orders WHERE order_number = ? LIMIT 1');
        $statement->execute([$orderNumber]);
        return $statement->fetchColumn() !== false;
    }

    /** @return array<int, array<string, mixed>> */
    public function forUser(int $userId, int $limit = 50): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, order_number, placed_at, total, payment_method, payment_status, order_status
             FROM orders WHERE user_id = ? ORDER BY placed_at DESC
             LIMIT ' . max(1, min($limit, 100))
        );
        $statement->execute([$userId]);
        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findOwnedByNumber(string $orderNumber, int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM orders WHERE order_number = ? AND user_id = ? LIMIT 1'
        );
        $statement->execute([$orderNumber, $userId]);
        $order = $statement->fetch();
        return $order ? $this->withItems($order) : null;
    }

    /** @return array<string, mixed>|null */
    public function findByNumber(string $orderNumber): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM orders WHERE order_number = ? LIMIT 1');
        $statement->execute([$orderNumber]);
        $order = $statement->fetch();
        return $order ? $this->withItems($order) : null;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $orderId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
        $statement->execute([$orderId]);
        $order = $statement->fetch();
        return $order ? $this->withItems($order) : null;
    }

    /** @param array<string, mixed> $order
     *  @return array<string, mixed>
     */
    private function withItems(array $order): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
        $statement->execute([(int) $order['id']]);
        $order['items'] = $statement->fetchAll();
        $order['confirmation_email_status'] = $this->confirmationEmailStatus((int) $order['id']);
        return $order;
    }
}
