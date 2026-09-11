<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

final class RecordingMailTransport implements MailTransportInterface
{
    public int $calls = 0;
    /** @var array<int, array<string, string>> */
    public array $messages = [];

    public function send(array $message): void
    {
        $this->calls++;
        $this->messages[] = $message;
    }
}

final class FailingMailTransport implements MailTransportInterface
{
    public int $calls = 0;

    public function send(array $message): void
    {
        $this->calls++;
        throw new RuntimeException('Synthetic SMTP failure.');
    }
}

ob_start();
$_SERVER['REMOTE_ADDR'] = '127.0.0.99';
$pdo = db();
$suffix = bin2hex(random_bytes(4));
$email = 'phase3-test-' . $suffix . '@example.test';
$otherEmail = 'phase3-owner-' . $suffix . '@example.test';
$expiredCode = 'EXPIRED' . strtoupper($suffix);
$userId = null;
$otherUserId = null;
$orderIds = [];
$addressId = null;
$originalStock = [];
$checks = 0;

function phase3_check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException('FAILED: ' . $message);
    }
    $checks++;
    echo 'PASS: ' . $message . PHP_EOL;
}

try {
    phase3_check((int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() === 10, 'ten approved products are seeded');
    phase3_check((int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn() === 5, 'five categories are seeded');
    phase3_check((int) $pdo->query('SELECT COUNT(*) FROM collections')->fetchColumn() === 4, 'four collections are seeded');
    phase3_check((int) $pdo->query('SELECT COUNT(*) FROM product_variants')->fetchColumn() === 54, 'all product variants are seeded');
    phase3_check((int) $pdo->query('SELECT stock_quantity FROM product_variants WHERE id = 1014')->fetchColumn() === 0, 'variant-level sold-out stock is preserved');

    $catalog = catalog_products();
    phase3_check(count($catalog) === 10 && isset($catalog[0]['variants']['black']['M']['id']), 'repository hydrates images, colors, stock, and variant IDs');
    phase3_check(catalog_product_by_slug('missing-piece') === null, 'invalid product slug returns no product');

    phase3_check(!verify_csrf('tampered'), 'tampered CSRF token is rejected');
    phase3_check(verify_csrf(csrf_token()), 'valid CSRF token is accepted');
    phase3_check(safe_return_path('https://attacker.example') === 'account/index.php', 'external login redirect is rejected');
    phase3_check(safe_return_path("account/index.php\r\nX-Test: injected") === 'account/index.php', 'control characters in login redirect are rejected');

    $cart = cart_service();
    $wishlist = wishlist_service();
    $cart->addVariant(1011, 2);
    $wishlist->toggle(101);
    phase3_check($cart->summary()['count'] === 2, 'guest session cart accepts a real variant');
    phase3_check(in_array(101, $wishlist->productIds(), true), 'guest session wishlist persists');

    $auth = auth_service();
    $registration = [
        'first_name' => 'Phase',
        'last_name' => 'Tester',
        'email' => $email,
        'password' => 'VantaTest123',
        'confirm_password' => 'VantaTest123',
    ];
    phase3_check($auth->registrationErrors($registration) === [], 'valid registration input passes validation');
    $userId = $auth->register($registration);
    phase3_check(!$auth->login($email, 'wrong-password'), 'wrong password is rejected generically');
    phase3_check($auth->login($email, $registration['password']), 'password_verify login succeeds');
    phase3_check(current_user_id() === $userId, 'authenticated session stores only the user identity');
    phase3_check($cart->summary()['count'] === 2, 'guest cart merges into the account cart');
    phase3_check(in_array(101, $wishlist->productIds(), true), 'guest wishlist merges into the account wishlist');

    try {
        $auth->register($registration);
        phase3_check(false, 'duplicate registration is rejected');
    } catch (DomainException) {
        phase3_check(true, 'duplicate registration is rejected');
    }

    $addresses = new AddressRepository($pdo);
    $addressId = $addresses->save($userId, [
        'label' => 'Studio',
        'recipient_name' => 'Phase Tester',
        'phone' => '+92 300 0000000',
        'address_line_1' => '10 Test Avenue',
        'address_line_2' => '',
        'city' => 'Karachi',
        'province' => 'Sindh',
        'postal_code' => '74000',
        'country' => 'Pakistan',
        'is_default' => 1,
    ]);
    phase3_check($addresses->ownedBy($addressId, $userId) !== null, 'address ownership query accepts the owner');

    $otherUserId = (new UserRepository($pdo))->create('Other', 'Owner', $otherEmail, password_hash('OtherPassword123', PASSWORD_DEFAULT));
    phase3_check($addresses->ownedBy($addressId, $otherUserId) === null, 'address ownership query rejects another user');

    try {
        coupon_service()->validate('NOTREAL', 20000, $userId, $email);
        phase3_check(false, 'invalid coupon is rejected');
    } catch (DomainException) {
        phase3_check(true, 'invalid coupon is rejected');
    }

    $insertExpired = $pdo->prepare(
        'INSERT INTO coupons (code,type,value,minimum_order,expires_at,is_active)
         VALUES (?,\'percentage\',10,0,DATE_SUB(NOW(),INTERVAL 1 DAY),1)'
    );
    $insertExpired->execute([$expiredCode]);
    try {
        coupon_service()->validate($expiredCode, 20000, $userId, $email);
        phase3_check(false, 'expired coupon is rejected');
    } catch (DomainException) {
        phase3_check(true, 'expired coupon is rejected');
    }

    foreach ([1011, 1091, 1073] as $variantId) {
        $statement = $pdo->prepare('SELECT stock_quantity FROM product_variants WHERE id = ?');
        $statement->execute([$variantId]);
        $originalStock[$variantId] = (int) $statement->fetchColumn();
    }

    $_SESSION['coupon_code'] = 'VANTA10';
    $checkout = [
        'first_name' => 'Phase', 'last_name' => 'Tester', 'email' => $email,
        'phone' => '+92 300 0000000', 'label' => 'Studio', 'recipient_name' => 'Phase Tester',
        'address_line_1' => '10 Test Avenue', 'address_line_2' => '', 'city' => 'Karachi',
        'province' => 'Sindh', 'postal_code' => '74000', 'country' => 'Pakistan',
        'payment_method' => 'demo_card',
    ];
    $recordingTransport = new RecordingMailTransport();
    $recordingMail = new MailService(
        new OrderRepository($pdo),
        $recordingTransport,
        new OrderConfirmationEmailBuilder()
    );
    $successfulMailOrderService = new OrderService(
        $pdo,
        product_repository(),
        $cart,
        coupon_service(),
        new CouponRepository($pdo),
        new OrderRepository($pdo),
        $recordingMail
    );
    $placed = $successfulMailOrderService->place($checkout);
    $orderIds[] = $placed['order_id'];
    phase3_check((bool) preg_match('/^VNT-\d{4}-\d{6}$/', $placed['order_number']), 'readable non-ID order number is generated');
    $order = (new OrderRepository($pdo))->findOwnedByNumber($placed['order_number'], $userId);
    phase3_check($order !== null && count($order['items']) === 1, 'order and immutable item snapshot are created');
    phase3_check((float) $order['subtotal'] === 13600.0 && (float) $order['discount_total'] === 1360.0, 'server calculates VANTA10 without client prices');
    phase3_check((float) $order['shipping_total'] === 300.0 && (float) $order['total'] === 12540.0, 'server calculates shipping and non-negative total');
    phase3_check($order['payment_method'] === 'demo_card' && $order['payment_status'] === 'paid', 'demo card is recorded only as a simulated payment method');
    phase3_check((int) $pdo->query('SELECT stock_quantity FROM product_variants WHERE id = 1011')->fetchColumn() === $originalStock[1011] - 2, 'inventory decreases inside successful order flow');
    phase3_check($cart->summary()['count'] === 0, 'account cart clears after successful commit');
    phase3_check((new OrderRepository($pdo))->findOwnedByNumber($placed['order_number'], $otherUserId) === null, 'order ownership rejects another customer');
    phase3_check($placed['email_sent'] === true && $recordingTransport->calls === 1, 'successful order confirmation email is sent after commit');
    $sentMessage = $recordingTransport->messages[0];
    phase3_check(
        str_contains($sentMessage['html'], 'VANTA')
        && str_contains($sentMessage['html'], $placed['order_number'])
        && str_contains($sentMessage['html'], 'VANTA Oversized Essential Tee')
        && str_contains($sentMessage['html'], (string) $order['items'][0]['variant_description'])
        && str_contains($sentMessage['text'], 'SHIPPING ADDRESS')
        && str_contains($sentMessage['text'], 'Payment method: Demo Card'),
        'HTML and plain-text email include complete order details'
    );
    $recordingMail->sendOrderConfirmation($placed['order_id']);
    phase3_check(
        $recordingTransport->calls === 1
        && (new OrderRepository($pdo))->confirmationEmailStatus($placed['order_id']) === 'sent',
        'a repeated confirmation request cannot send a duplicate email'
    );

    $reviews = new ReviewRepository($pdo);
    phase3_check($reviews->hasPurchased($userId, 101), 'purchased-product review eligibility is detected');
    $reviews->create($userId, 101, 5, 'Excellent weight, structured fit, and careful finishing.');
    phase3_check($reviews->approvedForProduct(101) === [], 'pending review is hidden publicly');
    $approve = $pdo->prepare('UPDATE reviews SET status = \'approved\' WHERE user_id = ? AND product_id = ?');
    $approve->execute([$userId, 101]);
    phase3_check(count($reviews->approvedForProduct(101)) === 1, 'approved review appears publicly');

    $newsletter = new NewsletterRepository($pdo);
    phase3_check($newsletter->subscribe($email) === 'created', 'newsletter subscription persists');
    phase3_check($newsletter->subscribe($email) === 'existing', 'duplicate newsletter subscription is prevented');

    $auth->logout();
    $cart->addVariant(1091, 1);
    $guestCheckout = $checkout;
    $guestCheckout['email'] = 'guest-' . $email;
    $guestCheckout['payment_method'] = 'cash_on_delivery';
    $failingTransport = new FailingMailTransport();
    $failingMail = new MailService(
        new OrderRepository($pdo),
        $failingTransport,
        new OrderConfirmationEmailBuilder()
    );
    $failingMailOrderService = new OrderService(
        $pdo,
        product_repository(),
        $cart,
        coupon_service(),
        new CouponRepository($pdo),
        new OrderRepository($pdo),
        $failingMail
    );
    $guestOrder = $failingMailOrderService->place($guestCheckout);
    $orderIds[] = $guestOrder['order_id'];
    $guestStored = (new OrderRepository($pdo))->findByNumber($guestOrder['order_number']);
    phase3_check($guestStored['user_id'] === null && $guestStored['payment_status'] === 'cod_pending', 'guest cash-on-delivery order is supported');
    phase3_check($cart->summary()['count'] === 0, 'guest cart clears only after successful order');
    phase3_check($guestOrder['email_sent'] === false && $failingTransport->calls === 1, 'failed SMTP delivery is reported without throwing from checkout');
    phase3_check(
        $guestStored !== null
        && $guestStored['confirmation_email_status'] === 'failed'
        && count($guestStored['items']) === 1,
        'valid order remains committed after confirmation email failure'
    );

    try {
        $cart->addVariant(1012, 0);
        phase3_check(false, 'tampered zero quantity is rejected');
    } catch (DomainException) {
        phase3_check(true, 'tampered zero quantity is rejected');
    }
    try {
        $cart->addVariant(1014, 1);
        phase3_check(false, 'out-of-stock variant is rejected server-side');
    } catch (DomainException) {
        phase3_check(true, 'out-of-stock variant is rejected server-side');
    }

    $cart->addVariant(1073, 1);
    $pdo->exec('UPDATE product_variants SET stock_quantity = 0 WHERE id = 1073');
    $ordersBefore = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    try {
        order_service()->place($guestCheckout);
        phase3_check(false, 'checkout rejects stock changed after cart add');
    } catch (DomainException) {
        phase3_check(true, 'checkout rejects stock changed after cart add');
    }
    phase3_check((int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn() === $ordersBefore, 'failed stock validation rolls back without a partial order');

    echo 'RESULT: ' . $checks . ' integration checks passed.' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    $failed = true;
} finally {
    unset($_SESSION['vanta_cart'], $_SESSION['vanta_wishlist'], $_SESSION['coupon_code'], $_SESSION['confirmed_orders']);
    if ($userId !== null) {
        $deleteReviews = $pdo->prepare('DELETE FROM reviews WHERE user_id = ?');
        $deleteReviews->execute([$userId]);
    }
    foreach ($orderIds as $orderId) {
        $deleteUsage = $pdo->prepare('DELETE FROM coupon_usage WHERE order_id = ?');
        $deleteUsage->execute([$orderId]);
        $deleteOrder = $pdo->prepare('DELETE FROM orders WHERE id = ?');
        $deleteOrder->execute([$orderId]);
    }
    foreach ($originalStock as $variantId => $quantity) {
        $restore = $pdo->prepare('UPDATE product_variants SET stock_quantity = ? WHERE id = ?');
        $restore->execute([$quantity, $variantId]);
    }
    $deleteNewsletter = $pdo->prepare('DELETE FROM newsletter_subscribers WHERE email IN (?, ?)');
    $deleteNewsletter->execute([$email, 'guest-' . $email]);
    $deleteAttempts = $pdo->prepare('DELETE FROM login_attempts WHERE email IN (?, ?)');
    $deleteAttempts->execute([$email, $otherEmail]);
    $deleteExpired = $pdo->prepare('DELETE FROM coupons WHERE code = ?');
    $deleteExpired->execute([$expiredCode]);
    if ($userId !== null || $otherUserId !== null) {
        $ids = array_values(array_filter([$userId, $otherUserId]));
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $deleteUsers = $pdo->prepare('DELETE FROM users WHERE id IN (' . $marks . ')');
        $deleteUsers->execute($ids);
    }
}

ob_end_flush();
exit(isset($failed) ? 1 : 0);
