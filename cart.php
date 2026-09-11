<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
$cart = cart_service()->summary();
$pageTitle = 'Your Bag — VANTA';
$pageDescription = 'Review your VANTA bag.';
$currentPage = 'cart';
$bodyClass = 'commerce-page';
$headerTheme = 'solid';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="cart-page" data-cart-page>
    <header class="commerce-hero container-vanta">
        <p class="eyebrow">SELECTION / BAG</p>
        <h1>Your bag.</h1>
        <p><span data-cart-page-count><?= (int) $cart['count'] ?></span> pieces selected.</p>
    </header>
    <div class="cart-layout container-vanta">
        <section class="cart-page-lines" data-cart-page-lines>
            <?php foreach ($cart['lines'] as $line): ?>
                <article data-cart-key="<?= (int) $line['variant_id'] ?>">
                    <a href="<?= e($line['url']) ?>"><img src="<?= e($line['image']) ?>" alt="" width="220" height="275"></a>
                    <div><p><?= e($line['collection']) ?></p><h2><a href="<?= e($line['url']) ?>"><?= e($line['name']) ?></a></h2><span><?= e($line['color']) ?> / <?= e($line['size']) ?> / <?= e($line['sku']) ?></span>
                        <div class="cart-line__actions"><div class="mini-quantity"><button type="button" data-cart-action="decrease">−</button><span><?= str_pad((string) $line['quantity'], 2, '0', STR_PAD_LEFT) ?></span><button type="button" data-cart-action="increase">+</button></div><button type="button" data-cart-action="remove">Remove</button></div>
                    </div>
                    <strong><?= e(format_pkr((float) $line['line_total'])) ?></strong>
                </article>
            <?php endforeach; ?>
        </section>
        <div class="account-empty" data-cart-page-empty <?= $cart['lines'] === [] ? '' : 'hidden' ?>><p>Your bag is empty.</p><span>The night is still young.</span><a class="button button--outline-dark" href="<?= e(url('shop.php')) ?>">Explore the store</a></div>
        <aside class="cart-totals" data-cart-page-summary <?= $cart['lines'] === [] ? 'hidden' : '' ?>>
            <p class="eyebrow">ORDER / ESTIMATE</p>
            <dl><div><dt>Subtotal</dt><dd data-cart-page-subtotal><?= e(format_pkr((float) $cart['subtotal'])) ?></dd></div><div><dt>Shipping</dt><dd>Calculated at checkout</dd></div></dl>
            <a class="button button--lime" href="<?= e(url('checkout.php')) ?>">Continue to checkout <?= icon('arrow-right', 'h-4 w-4') ?></a>
            <p>Prices and stock are revalidated securely before your order is placed.</p>
        </aside>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

