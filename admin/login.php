<?php

declare(strict_types=1);

require __DIR__.'/_init.php';
if(current_admin()!==null)admin_redirect();
$error='';$email='';$loginFlashes=pull_flashes();
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    try{
        admin_post_guard();$email=normalize_email((string)($_POST['email']??''));admin_service()->login($email,(string)($_POST['password']??''));
        $return=safe_return_path((string)($_POST['return']??''),'admin/index.php');if(!str_starts_with($return,'admin/'))$return='admin/index.php';redirect($return);
    }catch(Throwable$exception){$error=$exception instanceof DomainException?$exception->getMessage():'Unable to sign in right now.';}
}
$return=safe_return_path((string)($_GET['return']??$_POST['return']??''),'admin/index.php');if(!str_starts_with($return,'admin/'))$return='admin/index.php';
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#090909"><title>Sign in — VANTA Admin</title><link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' fill='%230A0A0A'/%3E%3Cpath d='M11 13h12l9 28 9-28h12L38 53H26z' fill='%23B7FF2A'/%3E%3C/svg%3E"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="<?= e(url('admin/assets/admin.css')) ?>"></head>
<body class="admin-login-body"><main class="login-card"><p class="login-kicker">VANTA / ADMIN</p><h1>Store control.</h1><p>Use your dedicated administrator account. Customer credentials cannot access this area.</p>
<?php if($error): ?><div class="login-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php foreach($loginFlashes as$notice): ?><div class="login-notice login-notice--<?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div><?php endforeach; ?>
<form method="post" autocomplete="on"><?= csrf_field() ?><input type="hidden" name="return" value="<?= e($return) ?>">
<label class="field"><span>Email</span><input type="email" name="email" value="<?= e($email) ?>" autocomplete="username" required autofocus></label>
<label class="field"><span>Password</span><input type="password" name="password" autocomplete="current-password" required></label>
<button class="button button--primary" type="submit">Sign in</button></form><p class="login-footer">Protected admin route · login attempts are rate limited</p></main></body></html>
