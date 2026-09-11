<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_auth();
$userId = (int) $user['id'];
$repository = new AddressRepository(db());
$errors = [];
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: null;
$editing = $editId ? $repository->ownedBy((int) $editId, $userId) : null;
if ($editId && $editing === null) {
    http_response_code(404);
    flash('error', 'Address not found.');
    redirect('account/addresses.php');
}
$defaults = [
    'label' => 'Home', 'recipient_name' => trim($user['first_name'] . ' ' . $user['last_name']),
    'phone' => '', 'address_line_1' => '', 'address_line_2' => '', 'city' => '',
    'province' => 'Sindh', 'postal_code' => '', 'country' => 'Pakistan', 'is_default' => '0',
];
$values = array_merge($defaults, $editing ?: []);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verify_csrf(request_csrf_token())) {
        $errors['form'] = 'Your session expired. Refresh and try again.';
    } else {
        $action = (string) ($_POST['action'] ?? 'save');
        $addressId = filter_var($_POST['address_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
        if ($action === 'delete') {
            if (!$addressId || !$repository->deleteOwned((int) $addressId, $userId)) {
                http_response_code(404);
                $errors['form'] = 'That address could not be found.';
            } else {
                flash('success', 'Address removed.');
                redirect('account/addresses.php');
            }
        } elseif ($action === 'default') {
            if (!$addressId || !$repository->setDefaultOwned((int) $addressId, $userId)) {
                http_response_code(404);
                $errors['form'] = 'That address could not be found.';
            } else {
                flash('success', 'Default address updated.');
                redirect('account/addresses.php');
            }
        } else {
            foreach (array_keys($defaults) as $key) {
                $values[$key] = $key === 'is_default'
                    ? (isset($_POST[$key]) ? '1' : '0')
                    : trim((string) ($_POST[$key] ?? ''));
            }
            if (mb_strlen((string) $values['label']) < 2) $errors['label'] = 'Add a short label.';
            if (mb_strlen((string) $values['recipient_name']) < 3) $errors['recipient_name'] = 'Enter the recipient name.';
            if (!preg_match('/^[0-9+() -]{7,30}$/', (string) $values['phone'])) $errors['phone'] = 'Enter a valid phone number.';
            if (mb_strlen((string) $values['address_line_1']) < 5) $errors['address_line_1'] = 'Enter the street address.';
            if (mb_strlen((string) $values['city']) < 2) $errors['city'] = 'Enter the city.';
            if (mb_strlen((string) $values['province']) < 2) $errors['province'] = 'Enter the province or region.';
            if (mb_strlen((string) $values['country']) < 2) $errors['country'] = 'Enter the country.';
            if ($addressId && $repository->ownedBy((int) $addressId, $userId) === null) {
                $errors['form'] = 'That address could not be found.';
            }
            if ($errors === []) {
                $repository->save($userId, $values, $addressId ? (int) $addressId : null);
                flash('success', $addressId ? 'Address updated.' : 'Address added.');
                redirect('account/addresses.php');
            }
        }
    }
}
$addresses = $repository->forUser($userId);
$accountSection = 'addresses';
$pageTitle = 'Addresses — VANTA';
$pageDescription = 'Manage your VANTA delivery addresses.';
$currentPage = 'account';
$bodyClass = 'commerce-page';
$headerTheme = 'solid';
require dirname(__DIR__) . '/includes/header.php';
?>
<main id="main-content" class="account-shell">
    <header class="account-hero account-hero--compact container-vanta"><p class="eyebrow">CUSTOMER / DELIVERY</p><h1>Saved places.</h1></header>
    <div class="account-layout container-vanta">
        <?php require dirname(__DIR__) . '/includes/account-nav.php'; ?>
        <section class="account-content">
            <?php foreach (pull_flashes() as $message): ?><p class="form-banner form-banner--<?= e($message['type']) ?>"><?= e($message['message']) ?></p><?php endforeach; ?>
            <?php if (isset($errors['form'])): ?><p class="form-banner form-banner--error"><?= e($errors['form']) ?></p><?php endif; ?>
            <div class="address-grid">
                <?php foreach ($addresses as $address): ?>
                    <article class="address-card<?= $address['is_default'] ? ' is-default' : '' ?>">
                        <div><p><?= e($address['label']) ?></p><?php if ($address['is_default']): ?><span>Default</span><?php endif; ?></div>
                        <strong><?= e($address['recipient_name']) ?></strong>
                        <address><?= e($address['address_line_1']) ?><br><?php if ($address['address_line_2']): ?><?= e($address['address_line_2']) ?><br><?php endif; ?><?= e($address['city']) ?>, <?= e($address['province']) ?> <?= e($address['postal_code']) ?><br><?= e($address['country']) ?><br><?= e($address['phone']) ?></address>
                        <div class="address-card__actions">
                            <a href="<?= e(url('account/addresses.php?edit=' . $address['id'] . '#address-form')) ?>">Edit</a>
                            <?php if (!$address['is_default']): ?>
                                <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="default"><input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>"><button>Set default</button></form>
                            <?php endif; ?>
                            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>"><button class="danger-link">Delete</button></form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="account-section-heading" id="address-form"><div><p class="eyebrow">ADDRESS / <?= $editing ? 'EDIT' : 'NEW' ?></p><h2><?= $editing ? 'Update place.' : 'Add a place.' ?></h2></div></div>
            <form class="vanta-form" method="post" novalidate>
                <?= csrf_field() ?><input type="hidden" name="action" value="save"><?php if ($editing): ?><input type="hidden" name="address_id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>
                <div class="field-row">
                    <label class="field"><span>Label</span><input name="label" value="<?= e((string) $values['label']) ?>" placeholder="Home" required><?php if (isset($errors['label'])): ?><small><?= e($errors['label']) ?></small><?php endif; ?></label>
                    <label class="field"><span>Recipient name</span><input name="recipient_name" value="<?= e((string) $values['recipient_name']) ?>" autocomplete="name" required><?php if (isset($errors['recipient_name'])): ?><small><?= e($errors['recipient_name']) ?></small><?php endif; ?></label>
                </div>
                <label class="field"><span>Phone</span><input name="phone" value="<?= e((string) $values['phone']) ?>" autocomplete="tel" required><?php if (isset($errors['phone'])): ?><small><?= e($errors['phone']) ?></small><?php endif; ?></label>
                <label class="field"><span>Address line 1</span><input name="address_line_1" value="<?= e((string) $values['address_line_1']) ?>" autocomplete="address-line1" required><?php if (isset($errors['address_line_1'])): ?><small><?= e($errors['address_line_1']) ?></small><?php endif; ?></label>
                <label class="field"><span>Address line 2 <em>Optional</em></span><input name="address_line_2" value="<?= e((string) $values['address_line_2']) ?>" autocomplete="address-line2"></label>
                <div class="field-row">
                    <label class="field"><span>City</span><input name="city" value="<?= e((string) $values['city']) ?>" autocomplete="address-level2" required><?php if (isset($errors['city'])): ?><small><?= e($errors['city']) ?></small><?php endif; ?></label>
                    <label class="field"><span>Province / region</span><input name="province" value="<?= e((string) $values['province']) ?>" autocomplete="address-level1" required><?php if (isset($errors['province'])): ?><small><?= e($errors['province']) ?></small><?php endif; ?></label>
                </div>
                <div class="field-row">
                    <label class="field"><span>Postal code <em>Optional</em></span><input name="postal_code" value="<?= e((string) $values['postal_code']) ?>" autocomplete="postal-code"></label>
                    <label class="field"><span>Country</span><input name="country" value="<?= e((string) $values['country']) ?>" autocomplete="country-name" required><?php if (isset($errors['country'])): ?><small><?= e($errors['country']) ?></small><?php endif; ?></label>
                </div>
                <label class="check-field"><input type="checkbox" name="is_default" value="1" <?= (string) $values['is_default'] === '1' ? 'checked' : '' ?>><span>Use as my default delivery address</span></label>
                <button class="button button--lime" type="submit"><?= $editing ? 'Save changes' : 'Add address' ?></button>
            </form>
        </section>
    </div>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

