<?php

declare(strict_types=1);

final class OrderService
{
    public function __construct(
        private PDO $pdo,
        private ProductRepository $products,
        private CartService $cart,
        private CouponService $coupons,
        private CouponRepository $couponRepository,
        private OrderRepository $orders,
        private MailService $mail
    ) {
    }

    /** @return array<string, mixed> */
    public function quote(string $email = ''): array
    {
        $cart = $this->cart->summary();
        $subtotal = (float) $cart['subtotal'];
        $discount = 0.0;
        $couponCode = (string) ($_SESSION['coupon_code'] ?? '');
        $coupon = null;
        $couponError = null;
        if ($couponCode !== '') {
            try {
                $result = $this->coupons->validate($couponCode, $subtotal, current_user_id(), $email);
                $coupon = $result['coupon'];
                $discount = $result['discount'];
            } catch (DomainException $exception) {
                $couponError = $exception->getMessage();
                unset($_SESSION['coupon_code']);
            }
        }
        $shippingRate = max(0.0, (float) setting('shipping_standard_pkr', '300'));
        $freeThreshold = max(0.0, (float) setting('free_shipping_threshold_pkr', '15000'));
        $shipping = $subtotal > 0 && $subtotal < $freeThreshold ? $shippingRate : 0.0;
        return $cart + [
            'discount' => $discount,
            'shipping' => $shipping,
            'total' => max(0.0, $subtotal - $discount + $shipping),
            'coupon' => $coupon,
            'coupon_error' => $couponError,
            'free_shipping_threshold' => $freeThreshold,
        ];
    }

    /** @param array<string, string> $input
     *  @return array{order_id:int,order_number:string,email_sent:bool,email_status:string}
     */
    public function place(array $input): array
    {
        foreach (['first_name', 'last_name', 'email', 'phone', 'recipient_name', 'address_line_1', 'city', 'province', 'country'] as $field) {
            if (trim((string) ($input[$field] ?? '')) === '') {
                throw new DomainException('Complete all required checkout fields.');
            }
        }
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Enter a valid checkout email address.');
        }
        $rawItems = $this->cart->rawItems();
        if ($rawItems === []) {
            throw new DomainException('Your bag is empty.');
        }
        $paymentMethod = $input['payment_method'] ?? '';
        if (!in_array($paymentMethod, ['cash_on_delivery', 'demo_card'], true)) {
            throw new DomainException('Choose a valid payment method.');
        }

        $this->pdo->beginTransaction();
        try {
            $items = [];
            $subtotal = 0.0;
            foreach ($rawItems as $item) {
                $variant = $this->products->variantById($item['variant_id'], true);
                $quantity = (int) $item['quantity'];
                if ($quantity < 1 || $quantity > 10) {
                    throw new DomainException('A bag item has an invalid quantity.');
                }
                if ($variant === null || !$variant['is_active'] || !$variant['product_active']) {
                    throw new DomainException('A product in your bag is no longer available.');
                }
                if ((int) $variant['stock_quantity'] < $quantity) {
                    throw new DomainException(
                        $variant['name'] . ' / ' . $variant['color_name'] . ' / ' . $variant['size']
                        . ' no longer has enough stock.'
                    );
                }
                $unitPrice = (float) ($variant['sale_price'] ?? $variant['price']);
                $lineTotal = $unitPrice * $quantity;
                $subtotal += $lineTotal;
                $items[] = [
                    'variant_id' => (int) $variant['id'],
                    'product_id' => (int) $variant['product_id'],
                    'name' => (string) $variant['name'],
                    'variant_description' => (string) $variant['color_name'] . ' / ' . (string) $variant['size'],
                    'sku' => (string) $variant['sku'],
                    'image_path' => (string) $variant['image_path'],
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ];
            }

            $coupon = null;
            $discount = 0.0;
            $couponCode = (string) ($_SESSION['coupon_code'] ?? '');
            if ($couponCode !== '') {
                $couponResult = $this->coupons->validate(
                    $couponCode,
                    $subtotal,
                    current_user_id(),
                    $input['email'],
                    true
                );
                $coupon = $couponResult['coupon'];
                $discount = $couponResult['discount'];
            }

            $shippingRate = max(0.0, (float) setting('shipping_standard_pkr', '300'));
            $freeThreshold = max(0.0, (float) setting('free_shipping_threshold_pkr', '15000'));
            $shipping = $subtotal >= $freeThreshold ? 0.0 : $shippingRate;
            $total = max(0.0, $subtotal - $discount + $shipping);
            $orderNumber = $this->uniqueOrderNumber();
            $paymentStatus = $paymentMethod === 'demo_card' ? 'paid' : 'cod_pending';
            $orderStatus = $paymentMethod === 'demo_card' ? 'processing' : 'pending';

            $orderId = $this->orders->create([
                'user_id' => current_user_id(),
                'coupon_id' => $coupon === null ? null : (int) $coupon['id'],
                'order_number' => $orderNumber,
                'customer_first_name' => $input['first_name'],
                'customer_last_name' => $input['last_name'],
                'customer_email' => normalize_email($input['email']),
                'customer_phone' => $input['phone'],
                'shipping_label' => $input['label'] ?: 'Delivery',
                'shipping_recipient' => $input['recipient_name'],
                'shipping_address_line_1' => $input['address_line_1'],
                'shipping_address_line_2' => $input['address_line_2'] ?: null,
                'shipping_city' => $input['city'],
                'shipping_province' => $input['province'],
                'shipping_postal_code' => $input['postal_code'] ?: null,
                'shipping_country' => $input['country'],
                'shipping_method' => 'Standard ' . (trim((string) setting('default_country', 'Pakistan')) ?: 'Pakistan') . ' Delivery',
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'order_status' => $orderStatus,
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'shipping_total' => $shipping,
                'total' => $total,
                'coupon_code' => $couponCode === '' ? null : $couponCode,
            ]);

            foreach ($items as $item) {
                if (!$this->orders->decrementStock($item['variant_id'], $item['quantity'])) {
                    throw new DomainException('Stock changed while the order was being placed. Your card was not charged.');
                }
                $this->orders->addItem($orderId, $item);
            }
            if ($coupon !== null) {
                $this->couponRepository->recordUsage(
                    (int) $coupon['id'],
                    current_user_id(),
                    $orderId,
                    normalize_email($input['email'])
                );
            }
            $this->orders->queueConfirmationEmail($orderId, normalize_email($input['email']));
            if (current_user_id() !== null) {
                $this->cart->clear();
            }
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }

        if (current_user_id() === null) {
            $this->cart->clear();
        }
        unset($_SESSION['coupon_code']);
        $allowed = (array) ($_SESSION['confirmed_orders'] ?? []);
        array_unshift($allowed, $orderNumber);
        $_SESSION['confirmed_orders'] = array_slice(array_values(array_unique($allowed)), 0, 5);

        $delivery = ['sent' => false, 'status' => 'failed'];
        try {
            $delivery = $this->mail->sendOrderConfirmation($orderId);
        } catch (Throwable $exception) {
            error_log(sprintf(
                'VANTA post-commit mail boundary failed for order ID %d (%s).',
                $orderId,
                $exception::class
            ));
        }

        return [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'email_sent' => (bool) $delivery['sent'],
            'email_status' => (string) $delivery['status'],
        ];
    }

    private function uniqueOrderNumber(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $number = 'VNT-' . date('Y') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            if (!$this->orders->orderNumberExists($number)) {
                return $number;
            }
        }
        throw new RuntimeException('A unique order number could not be generated.');
    }
}
