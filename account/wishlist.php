<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_auth();
$ids = wishlist_service()->productIds();
$products = array_values(array_filter(catalog_products(), static fn (array $product): bool => in_array((int) $product['id'], $ids, true)));
$accountSection = 'wishlist';
$pageTitle = 'Wishlist — VANTA';
$pageDescription = 'Your saved VANTA pieces.';
$currentPage = 'account';
$bodyClass = 'commerce-page';
$headerTheme = 'solid';
require dirname(__DIR__) . '/includes/header.php';
?>
<main id="main-content" class="account-shell">
    <header class="account-hero account-hero--compact container-vanta"><p class="eyebrow">CUSTOMER / SAVED</p><h1>Your edit.</h1></header>
    <div class="account-layout container-vanta">
        <?php require dirname(__DIR__) . '/includes/account-nav.php'; ?>
        <section class="account-content">
            <?php if ($products === []): ?>
                <div class="account-empty"><p>Nothing saved yet.</p><span>Tap the heart on any piece to keep it close.</span><a class="button button--outline-dark" href="<?= e(url('shop.php')) ?>">Explore the store</a></div>
            <?php else: ?>
                <div class="product-grid account-product-grid"><?php foreach ($products as $index => $product) render_product_card($product, $index); ?></div>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

