<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$returnTo = safe_return_path((string) ($_GET['return'] ?? $_POST['return'] ?? 'account/index.php'));
if (current_user() !== null) {
    redirect($returnTo);
}
$errors = [];
$email = normalize_email((string) ($_POST['email'] ?? ''));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verify_csrf(request_csrf_token())) {
        $errors['form'] = 'Your session expired. Refresh and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || (string) ($_POST['password'] ?? '') === '') {
        $errors['form'] = 'Invalid email or password.';
    } else {
        try {
            if (auth_service()->login($email, (string) $_POST['password'])) {
                flash('success', 'Welcome back to VANTA.');
                redirect($returnTo);
            }
            $errors['form'] = 'Invalid email or password.';
        } catch (DomainException $exception) {
            $errors['form'] = $exception->getMessage();
        }
    }
}

$pageTitle = 'Sign In — VANTA';
$pageDescription = 'Sign in to your VANTA customer account.';
$currentPage = 'account';
$bodyClass = 'commerce-page';
$headerTheme = 'solid';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="auth-shell">
    <section class="auth-panel">
        <div class="auth-panel__intro">
            <p class="eyebrow">CUSTOMER / ACCESS</p>
            <h1>Return to<br>the night.</h1>
            <p>Your bag and guest wishlist merge into your account when you sign in.</p>
        </div>
        <form class="vanta-form auth-form" method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="return" value="<?= e($returnTo) ?>">
            <?php if (isset($errors['form'])): ?><p class="form-banner form-banner--error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
            <label class="field">
                <span>Email address</span>
                <input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
            </label>
            <label class="field">
                <span>Password</span>
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <button class="button button--lime" type="submit">Sign in <?= icon('arrow-right', 'h-4 w-4') ?></button>
            <p class="form-switch">New to VANTA? <a href="<?= e(url('register.php?return=' . rawurlencode($returnTo))) ?>">Create an account</a></p>
        </form>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

