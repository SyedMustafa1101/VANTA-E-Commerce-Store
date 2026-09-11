<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
$user = current_user();
$address = null;
if ($user !== null) {
    $savedAddresses = (new AddressRepository(db()))->forUser((int) $user['id']);
    $address = $savedAddresses[0] ?? null;
}
$defaults = [
    'first_name' => (string) ($user['first_name'] ?? ''),
    'last_name' => (string) ($user['last_name'] ?? ''),
    'email' => (string) ($user['email'] ?? ''),
    'phone' => (string) ($address['phone'] ?? ''),
    'label' => (string) ($address['label'] ?? 'Delivery'),
    'recipient_name' => (string) ($address['recipient_name'] ?? ($user ? trim($user['first_name'] . ' ' . $user['last_name']) : '')),
    'address_line_1' => (string) ($address['address_line_1'] ?? ''),
    'address_line_2' => (string) ($address['address_line_2'] ?? ''),
    'city' => (string) ($address['city'] ?? ''),
    'province' => (string) ($address['province'] ?? 'Sindh'),
    'postal_code' => (string) ($address['postal_code'] ?? ''),
    'country' => (string) ($address['country'] ?? 'Pakistan'),
    'payment_method' => 'cash_on_delivery',
];
$values = $defaults;
$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach (array_keys($defaults) as $key) {
        $values[$key] = trim((string) ($_POST[$key] ?? $defaults[$key]));
    }
    if (!verify_csrf(request_csrf_token())) $errors['form'] = 'Your session expired. Refresh and try again.';
    if (mb_strlen($values['first_name']) < 2) $errors['first_name'] = 'Enter your first name.';
    if (mb_strlen($values['last_name']) < 2) $errors['last_name'] = 'Enter your last name.';
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email.';
    if (!preg_match('/^[0-9+() -]{7,30}$/', $values['phone'])) $errors['phone'] = 'Enter a valid phone number.';
    if (mb_strlen($values['recipient_name']) < 3) $errors['recipient_name'] = 'Enter the recipient name.';
    if (mb_strlen($values['address_line_1']) < 5) $errors['address_line_1'] = 'Enter the street address.';
    if (mb_strlen($values['city']) < 2) $errors['city'] = 'Enter the city.';
    if (mb_strlen($values['province']) < 2) $errors['province'] = 'Enter the province or region.';
    if (mb_strlen($values['country']) < 2) $errors['country'] = 'Enter the country.';
    if (!in_array($values['payment_method'], ['cash_on_delivery', 'demo_card'], true)) $errors['payment_method'] = 'Choose a payment method.';
    if ($errors === []) {
        try {
            $placed = order_service()->place($values);
            redirect('order-confirmation.php?order=' . rawurlencode($placed['order_number']));
        } catch (DomainException $exception) {
            $errors['form'] = $exception->getMessage();
        }
    }
}
$quote = order_service()->quote($values['email']);
$pageTitle = 'Checkout — VANTA';
$pageDescription = 'Securely complete your VANTA order.';
$currentPage = 'checkout';
$bodyClass = 'commerce-page';
$headerTheme = 'solid';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="checkout-page">
    <header class="commerce-hero container-vanta"><p class="eyebrow">ORDER / CHECKOUT</p><h1>Finish the edit.</h1><p>Server-verified pricing, stock, shipping, and discounts.</p></header>
    <?php if ($quote['lines'] === []): ?>
        <div class="account-empty container-vanta"><p>Your bag is empty.</p><span>Add a piece before starting checkout.</span><a class="button button--outline-dark" href="<?= e(url('shop.php')) ?>">Explore the store</a></div>
    <?php else: ?>
    <form class="checkout-layout container-vanta" method="post" novalidate>
        <?= csrf_field() ?>
        <div class="checkout-form">
            <?php if (isset($errors['form'])): ?><p class="form-banner form-banner--error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
            <section class="checkout-step"><div class="checkout-step__title"><span>01</span><div><p>Customer information</p><h2>Who is ordering?</h2></div></div>
                <div class="vanta-form">
                    <div class="field-row"><label class="field"><span>First name</span><input name="first_name" value="<?= e($values['first_name']) ?>" autocomplete="given-name" required><?php if (isset($errors['first_name'])): ?><small><?= e($errors['first_name']) ?></small><?php endif; ?></label><label class="field"><span>Last name</span><input name="last_name" value="<?= e($values['last_name']) ?>" autocomplete="family-name" required><?php if (isset($errors['last_name'])): ?><small><?= e($errors['last_name']) ?></small><?php endif; ?></label></div>
                    <div class="field-row"><label class="field"><span>Email address</span><input type="email" name="email" value="<?= e($values['email']) ?>" autocomplete="email" required><?php if (isset($errors['email'])): ?><small><?= e($errors['email']) ?></small><?php endif; ?></label><label class="field"><span>Phone</span><input name="phone" value="<?= e($values['phone']) ?>" autocomplete="tel" required><?php if (isset($errors['phone'])): ?><small><?= e($errors['phone']) ?></small><?php endif; ?></label></div>
                </div>
            </section>
            <section class="checkout-step"><div class="checkout-step__title"><span>02</span><div><p>Shipping address</p><h2>Where is it going?</h2></div></div>
                <div class="vanta-form">
                    <input type="hidden" name="label" value="<?= e($values['label']) ?>">
                    <label class="field"><span>Recipient name</span><input name="recipient_name" value="<?= e($values['recipient_name']) ?>" autocomplete="name" required><?php if (isset($errors['recipient_name'])): ?><small><?= e($errors['recipient_name']) ?></small><?php endif; ?></label>
                    <label class="field"><span>Address line 1</span><input name="address_line_1" value="<?= e($values['address_line_1']) ?>" autocomplete="address-line1" required><?php if (isset($errors['address_line_1'])): ?><small><?= e($errors['address_line_1']) ?></small><?php endif; ?></label>
                    <label class="field"><span>Address line 2 <em>Optional</em></span><input name="address_line_2" value="<?= e($values['address_line_2']) ?>" autocomplete="address-line2"></label>
                    <div class="field-row"><label class="field"><span>City</span><input name="city" value="<?= e($values['city']) ?>" autocomplete="address-level2" required><?php if (isset($errors['city'])): ?><small><?= e($errors['city']) ?></small><?php endif; ?></label><label class="field"><span>Province / region</span><input name="province" value="<?= e($values['province']) ?>" autocomplete="address-level1" required><?php if (isset($errors['province'])): ?><small><?= e($errors['province']) ?></small><?php endif; ?></label></div>
                    <div class="field-row"><label class="field"><span>Postal code <em>Optional</em></span><input name="postal_code" value="<?= e($values['postal_code']) ?>" autocomplete="postal-code"></label><label class="field"><span>Country</span><input name="country" value="<?= e($values['country']) ?>" autocomplete="country-name" required><?php if (isset($errors['country'])): ?><small><?= e($errors['country']) ?></small><?php endif; ?></label></div>
                </div>
            </section>
            <section class="checkout-step"><div class="checkout-step__title"><span>03</span><div><p>Shipping method</p><h2>Standard Pakistan delivery.</h2></div></div><div class="checkout-choice is-selected"><span>Standard</span><strong><?= $quote['shipping'] > 0 ? e(format_pkr($quote['shipping'])) : 'Free' ?></strong><small>Estimated 2–5 working days</small></div></section>
            <section class="checkout-step"><div class="checkout-step__title"><span>04</span><div><p>Payment method</p><h2>Choose how to pay.</h2></div></div>
                <div class="payment-options">
                    <label><input type="radio" name="payment_method" value="cash_on_delivery" <?= $values['payment_method'] === 'cash_on_delivery' ? 'checked' : '' ?>><span><strong>Cash on Delivery</strong><small>Payment is due when your order arrives.</small></span></label>
                    <label><input type="radio" name="payment_method" value="demo_card" <?= $values['payment_method'] === 'demo_card' ? 'checked' : '' ?>><span><strong>Demo Card</strong><small>DEMO / SIMULATED PAYMENT — no card data is collected or stored.</small></span></label>
                </div>
                <?php if (isset($errors['payment_method'])): ?><p class="field-error"><?= e($errors['payment_method']) ?></p><?php endif; ?>
            </section>
        </div>
        <aside class="checkout-summary">
            <p class="eyebrow">05 / ORDER REVIEW</p>
            <div class="checkout-items"><?php foreach ($quote['lines'] as $line): ?><article><img src="<?= e($line['image']) ?>" alt="" width="120" height="150"><div><strong><?= e($line['name']) ?></strong><span><?= e($line['color']) ?> / <?= e($line['size']) ?> × <?= (int) $line['quantity'] ?></span></div><b><?= e(format_pkr($line['line_total'])) ?></b></article><?php endforeach; ?></div>
            <div class="coupon-box" data-coupon-form>
                <label><span>Coupon</span><div><input name="coupon_display" value="<?= e((string) ($_SESSION['coupon_code'] ?? '')) ?>" placeholder="VANTA10" data-coupon-code><button type="button" data-coupon-apply>Apply</button></div></label>
                <p data-coupon-feedback><?= $quote['coupon'] ? e($quote['coupon']['code'] . ' applied.') : e((string) ($quote['coupon_error'] ?? '')) ?></p>
            </div>
            <dl class="checkout-totals"><div><dt>Subtotal</dt><dd data-quote-subtotal><?= e(format_pkr($quote['subtotal'])) ?></dd></div><div><dt>Discount</dt><dd data-quote-discount>−<?= e(format_pkr($quote['discount'])) ?></dd></div><div><dt>Shipping</dt><dd data-quote-shipping><?= $quote['shipping'] > 0 ? e(format_pkr($quote['shipping'])) : 'Free' ?></dd></div><div><dt>Total</dt><dd data-quote-total><?= e(format_pkr($quote['total'])) ?></dd></div></dl>
            <button class="button button--lime" type="submit">Place order <?= icon('arrow-right', 'h-4 w-4') ?></button>
            <p class="checkout-assurance">Your order is created once. Inventory and totals are protected inside one database transaction.</p>
        </aside>
    </form>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

