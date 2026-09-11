<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_auth();
$orders = (new OrderRepository(db()))->forUser((int) $user['id']);
$accountSection = 'orders';
$pageTitle = 'Orders — VANTA';
$pageDescription = 'Your VANTA order history.';
$currentPage = 'account';
$bodyClass = 'commerce-page';
$headerTheme = 'solid';
require dirname(__DIR__) . '/includes/header.php';
?>
<main id="main-content" class="account-shell">
    <header class="account-hero account-hero--compact container-vanta"><p class="eyebrow">CUSTOMER / ORDERS</p><h1>Order history.</h1></header>
    <div class="account-layout container-vanta">
        <?php require dirname(__DIR__) . '/includes/account-nav.php'; ?>
        <section class="account-content">
            <?php if ($orders === []): ?>
                <div class="account-empty"><p>No orders yet.</p><span>Your first VANTA order will appear here.</span><a class="button button--outline-dark" href="<?= e(url('shop.php')) ?>">Explore the store</a></div>
            <?php else: ?>
                <div class="order-list order-list--full">
                    <div class="order-list__labels"><span>Order</span><span>Date</span><span>Status</span><span>Total</span></div>
                    <?php foreach ($orders as $order): ?>
                        <a href="<?= e(url('account/order.php?order=' . rawurlencode((string) $order['order_number']))) ?>">
                            <strong><?= e($order['order_number']) ?></strong><span><?= e(date('d M Y', strtotime((string) $order['placed_at']))) ?></span>
                            <span><?= e(format_status((string) $order['order_status'])) ?> / <?= e(format_status((string) $order['payment_status'])) ?></span>
                            <b><?= e(format_pkr((float) $order['total'])) ?></b>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

