<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$returnTo = safe_return_path((string) ($_GET['return'] ?? $_POST['return'] ?? 'account/index.php'));
if (current_user() !== null) {
    redirect($returnTo);
}
$values = [
    'first_name' => trim((string) ($_POST['first_name'] ?? '')),
    'last_name' => trim((string) ($_POST['last_name'] ?? '')),
    'email' => normalize_email((string) ($_POST['email'] ?? '')),
    'password' => (string) ($_POST['password'] ?? ''),
    'confirm_password' => (string) ($_POST['confirm_password'] ?? ''),
];
$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verify_csrf(request_csrf_token())) {
        $errors['form'] = 'Your session expired. Refresh and try again.';
    } else {
        $errors = auth_service()->registrationErrors($values);
        if ($errors === []) {
            try {
                auth_service()->register($values);
                auth_service()->login($values['email'], $values['password']);
                flash('success', 'Your VANTA account is ready.');
                redirect($returnTo);
            } catch (DomainException $exception) {
                $errors['email'] = $exception->getMessage();
            } catch (PDOException $exception) {
                if ((string) $exception->getCode() === '23000') {
                    $errors['email'] = 'An account already uses that email address.';
                } else {
                    throw $exception;
                }
            }
        }
    }
}

$pageTitle = 'Create Account — VANTA';
$pageDescription = 'Create a VANTA customer account.';
$currentPage = 'account';
$bodyClass = 'commerce-page';
$headerTheme = 'solid';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="auth-shell">
    <section class="auth-panel auth-panel--register">
        <div class="auth-panel__intro">
            <p class="eyebrow">CUSTOMER / JOIN</p>
            <h1>Enter the<br>VANTA world.</h1>
            <p>Save your edit, keep your bag across devices, and track every order.</p>
        </div>
        <form class="vanta-form auth-form" method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="return" value="<?= e($returnTo) ?>">
            <?php if (isset($errors['form'])): ?><p class="form-banner form-banner--error" role="alert"><?= e($errors['form']) ?></p><?php endif; ?>
            <div class="field-row">
                <label class="field"><span>First name</span><input name="first_name" value="<?= e($values['first_name']) ?>" autocomplete="given-name" required><?php if (isset($errors['first_name'])): ?><small><?= e($errors['first_name']) ?></small><?php endif; ?></label>
                <label class="field"><span>Last name</span><input name="last_name" value="<?= e($values['last_name']) ?>" autocomplete="family-name" required><?php if (isset($errors['last_name'])): ?><small><?= e($errors['last_name']) ?></small><?php endif; ?></label>
            </div>
            <label class="field"><span>Email address</span><input type="email" name="email" value="<?= e($values['email']) ?>" autocomplete="email" required><?php if (isset($errors['email'])): ?><small><?= e($errors['email']) ?></small><?php endif; ?></label>
            <div class="field-row">
                <label class="field"><span>Password</span><input type="password" name="password" autocomplete="new-password" required><?php if (isset($errors['password'])): ?><small><?= e($errors['password']) ?></small><?php endif; ?></label>
                <label class="field"><span>Confirm password</span><input type="password" name="confirm_password" autocomplete="new-password" required><?php if (isset($errors['confirm_password'])): ?><small><?= e($errors['confirm_password']) ?></small><?php endif; ?></label>
            </div>
            <p class="field-hint">At least 10 characters, including a letter and a number.</p>
            <button class="button button--lime" type="submit">Create account <?= icon('arrow-right', 'h-4 w-4') ?></button>
            <p class="form-switch">Already registered? <a href="<?= e(url('login.php?return=' . rawurlencode($returnTo))) ?>">Sign in</a></p>
        </form>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

