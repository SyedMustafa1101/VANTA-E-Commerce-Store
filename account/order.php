<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_auth();
$number = trim((string) ($_GET['order'] ?? ''));
$order = (new OrderRepository(db()))->findOwnedByNumber($number, (int) $user['id']);
if ($order === null) {
    http_response_code(404);
}
$accountSection = 'orders';
$pageTitle = ($order ? $order['order_number'] : 'Order Not Found') . ' — VANTA';
$pageDescription = 'VANTA order details.';
$currentPage = 'account';
$bodyClass = 'commerce-page';
$headerTheme = 'solid';
require dirname(__DIR__) . '/includes/header.php';
?>
<main id="main-content" class="account-shell">
    <header class="account-hero account-hero--compact container-vanta"><p class="eyebrow">CUSTOMER / ORDER</p><h1><?= $order ? e($order['order_number']) : 'Order not found.' ?></h1></header>
    <div class="account-layout container-vanta">
        <?php require dirname(__DIR__) . '/includes/account-nav.php'; ?>
        <section class="account-content">
            <?php if ($order === null): ?>
                <div class="account-empty"><p>That order is not available.</p><span>Only orders owned by this account can be viewed.</span><a class="button button--outline-dark" href="<?= e(url('account/orders.php')) ?>">Back to orders</a></div>
            <?php else: ?>
                <?php require dirname(__DIR__) . '/includes/order-detail.php'; ?>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

