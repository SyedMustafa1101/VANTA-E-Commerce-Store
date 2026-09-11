<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
$number = trim((string) ($_GET['order'] ?? ''));
$repository = new OrderRepository(db());
$user = current_user();
if ($user !== null) {
    $order = $repository->findOwnedByNumber($number, (int) $user['id']);
} elseif (in_array($number, (array) ($_SESSION['confirmed_orders'] ?? []), true)) {
    $order = $repository->findByNumber($number);
} else {
    $order = null;
}
if ($order === null) http_response_code(404);
$pageTitle = ($order ? 'Order ' . $order['order_number'] : 'Order Not Found') . ' — VANTA';
$pageDescription = 'Your VANTA order confirmation.';
$currentPage = 'confirmation';
$bodyClass = 'commerce-page';
$headerTheme = 'solid';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="confirmation-page">
    <?php if ($order === null): ?>
        <div class="account-empty container-vanta"><p>Order not found.</p><span>This confirmation is not available in your current session.</span><a class="button button--outline-dark" href="<?= e(url('shop.php')) ?>">Return to shop</a></div>
    <?php else: ?>
        <header class="confirmation-hero container-vanta" data-reveal>
            <p class="eyebrow">ORDER / CONFIRMED</p>
            <span class="confirmation-mark">✓</span>
            <h1>The night<br>is yours.</h1>
            <p>Your order <strong><?= e($order['order_number']) ?></strong> has been placed successfully.</p>
            <?php if (($order['confirmation_email_status'] ?? null) === 'sent'): ?>
                <p class="confirmation-email-note confirmation-email-note--sent" role="status">A confirmation email was sent to <?= e($order['customer_email']) ?>.</p>
            <?php elseif (($order['confirmation_email_status'] ?? null) === 'failed'): ?>
                <p class="confirmation-email-note confirmation-email-note--failed" role="status">Your order was placed successfully, but the confirmation email could not be sent.</p>
            <?php else: ?>
                <p class="confirmation-email-note" role="status">Your confirmation email is being prepared.</p>
            <?php endif; ?>
        </header>
        <section class="confirmation-detail container-vanta"><?php require __DIR__ . '/includes/order-detail.php'; ?><div class="confirmation-actions"><a class="button button--lime" href="<?= e(url('shop.php')) ?>">Continue shopping</a><?php if ($user): ?><a class="button button--outline-dark" href="<?= e(url('account/orders.php')) ?>">View order history</a><?php endif; ?></div></section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
