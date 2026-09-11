<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
$user = require_auth();
$errors = [];
$values = [
    'first_name' => trim((string) ($_POST['first_name'] ?? $user['first_name'])),
    'last_name' => trim((string) ($_POST['last_name'] ?? $user['last_name'])),
    'email' => normalize_email((string) ($_POST['email'] ?? $user['email'])),
];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verify_csrf(request_csrf_token())) $errors['form'] = 'Your session expired. Refresh and try again.';
    if (mb_strlen($values['first_name']) < 2) $errors['first_name'] = 'Enter your first name.';
    if (mb_strlen($values['last_name']) < 2) $errors['last_name'] = 'Enter your last name.';
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    $existing = (new UserRepository(db()))->findByEmail($values['email']);
    if ($existing !== null && (int) $existing['id'] !== (int) $user['id']) $errors['email'] = 'That email is already in use.';
    if ($errors === []) {
        (new UserRepository(db()))->updateProfile((int) $user['id'], $values['first_name'], $values['last_name'], $values['email']);
        reset_current_user_cache();
        flash('success', 'Profile updated.');
        redirect('account/profile.php');
    }
}
$accountSection = 'profile';
$pageTitle = 'Profile — VANTA';
$pageDescription = 'Manage your VANTA profile.';
$currentPage = 'account';
$bodyClass = 'commerce-page';
$headerTheme = 'solid';
require dirname(__DIR__) . '/includes/header.php';
?>
<main id="main-content" class="account-shell">
    <header class="account-hero account-hero--compact container-vanta"><p class="eyebrow">CUSTOMER / PROFILE</p><h1>Your identity.</h1></header>
    <div class="account-layout container-vanta">
        <?php require dirname(__DIR__) . '/includes/account-nav.php'; ?>
        <section class="account-content account-content--narrow">
            <?php foreach (pull_flashes() as $message): ?><p class="form-banner form-banner--<?= e($message['type']) ?>"><?= e($message['message']) ?></p><?php endforeach; ?>
            <form class="vanta-form" method="post" novalidate>
                <?= csrf_field() ?>
                <?php if (isset($errors['form'])): ?><p class="form-banner form-banner--error"><?= e($errors['form']) ?></p><?php endif; ?>
                <div class="field-row">
                    <label class="field"><span>First name</span><input name="first_name" value="<?= e($values['first_name']) ?>" required><?php if (isset($errors['first_name'])): ?><small><?= e($errors['first_name']) ?></small><?php endif; ?></label>
                    <label class="field"><span>Last name</span><input name="last_name" value="<?= e($values['last_name']) ?>" required><?php if (isset($errors['last_name'])): ?><small><?= e($errors['last_name']) ?></small><?php endif; ?></label>
                </div>
                <label class="field"><span>Email address</span><input type="email" name="email" value="<?= e($values['email']) ?>" required><?php if (isset($errors['email'])): ?><small><?= e($errors['email']) ?></small><?php endif; ?></label>
                <button class="button button--lime" type="submit">Save profile</button>
            </form>
        </section>
    </div>
</main>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

