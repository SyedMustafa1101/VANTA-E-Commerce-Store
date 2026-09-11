<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_auth();
$addresses = (new AddressRepository(db()))->forUser((int) $user['id']);
$orders = (new OrderRepository(db()))->forUser((int) $user['id'], 3);
$wishlistCount = count(wishlist_service()->productIds());
$accountSection = 'overview';
$pageTitle = 'My Account — VANTA';
$pageDescription = 'Your VANTA account overview.';
$currentPage = 'account';
$bodyClass = 'commerce-page';
$headerTheme = 'solid';
require dirname(__DIR__) . '/includes/header.php';
?>
<main id="main-content" class="account-shell">
    <header class="account-hero container-vanta">
        <p class="eyebrow">CUSTOMER / OVERVIEW</p>
        <h1>Welcome back,<br><?= e($user['first_name']) ?>.</h1>
        <p><?= e($user['email']) ?></p>
    </header>
    <div class="account-layout container-vanta">
        <?php require dirname(__DIR__) . '/includes/account-nav.php'; ?>
        <section class="account-content">
            <?php foreach (pull_flashes() as $message): ?><p class="form-banner form-banner--<?= e($message['type']) ?>"><?= e($message['message']) ?></p><?php endforeach; ?>
            <div class="account-stats">
                <a href="<?= e(url('account/orders.php')) ?>"><span>Orders</span><strong><?= count($orders) ?></strong><small>Recent activity</small></a>
                <a href="<?= e(url('account/addresses.php')) ?>"><span>Addresses</span><strong><?= count($addresses) ?></strong><small>Saved delivery points</small></a>
                <a href="<?= e(url('account/wishlist.php')) ?>"><span>Wishlist</span><strong><?= $wishlistCount ?></strong><small>Pieces kept close</small></a>
            </div>
            <div class="account-section-heading"><div><p class="eyebrow">RECENT / ORDERS</p><h2>Order history.</h2></div><a class="text-link" href="<?= e(url('account/orders.php')) ?>">View all <?= icon('arrow-right', 'h-4 w-4') ?></a></div>
            <?php if ($orders === []): ?>
                <div class="account-empty"><p>No orders yet.</p><span>Your first VANTA order will appear here.</span><a class="button button--outline-dark" href="<?= e(url('shop.php')) ?>">Explore the store</a></div>
            <?php else: ?>
                <div class="order-list">
                    <?php foreach ($orders as $order): ?>
                        <a href="<?= e(url('account/order.php?order=' . rawurlencode((string) $order['order_number']))) ?>">
                            <strong><?= e($order['order_number']) ?></strong><span><?= e(date('d M Y', strtotime((string) $order['placed_at']))) ?></span>
                            <span><?= e(format_status((string) $order['order_status'])) ?></span><b><?= e(format_pkr((float) $order['total'])) ?></b>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

