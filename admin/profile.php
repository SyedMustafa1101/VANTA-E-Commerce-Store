<?php

declare(strict_types=1);

require __DIR__.'/_init.php';$admin=require_admin();
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    try{
        admin_post_guard();$name=trim((string)($_POST['name']??''));$email=normalize_email((string)($_POST['email']??''));$current=(string)($_POST['current_password']??'');$new=(string)($_POST['new_password']??'');
        if(mb_strlen($name)<2||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new DomainException('Enter a valid name and email.');
        $record=admin_repository()->findAdminByEmail((string)$admin['email']);if(!$record||!password_verify($current,(string)$record['password_hash']))throw new DomainException('Current password is incorrect.');
        admin_repository()->updateAdmin((int)$admin['id'],$name,$email,(string)$admin['role'],true);
        if($new!==''){if(strlen($new)<12)throw new DomainException('New passwords must be at least 12 characters.');admin_repository()->updateAdminPassword((int)$admin['id'],password_hash($new,PASSWORD_DEFAULT));}
        admin_repository()->audit((int)$admin['id'],'admin.profile','admin',(int)$admin['id'],'Updated own admin profile.');reset_current_admin_cache();flash('success','Profile updated.');admin_redirect('profile.php');
    }catch(Throwable$exception){admin_flash_exception($exception);admin_redirect('profile.php');}
}
$adminTitle='Profile';$adminPage='profile';$adminSection='System';require __DIR__.'/_header.php';
?>
<div class="form-grid"><form method="post" class="form-section form-stack"><?= csrf_field() ?><h2>Account details</h2>
<label class="field"><span>Name</span><input name="name" value="<?= e((string)$admin['name']) ?>" required></label>
<label class="field"><span>Email</span><input type="email" name="email" value="<?= e((string)$admin['email']) ?>" required></label>
<label class="field"><span>Current password</span><input type="password" name="current_password" autocomplete="current-password" required></label>
<label class="field"><span>New password</span><input type="password" name="new_password" minlength="12" autocomplete="new-password"><small>Leave blank to keep the current password.</small></label>
<div class="form-footer"><button class="button button--primary" type="submit">Save profile</button></div></form>
<aside class="form-section sticky-card"><h2>Access</h2><dl class="definition-list"><dt>Role</dt><dd><span class="status status--active"><?= e(format_status((string)$admin['role'])) ?></span></dd><dt>Last login</dt><dd><?= e((string)($admin['last_login_at']??'First session')) ?></dd><dt>Created</dt><dd><?= e((string)$admin['created_at']) ?></dd></dl></aside></div>
<?php require __DIR__.'/_footer.php'; ?>
