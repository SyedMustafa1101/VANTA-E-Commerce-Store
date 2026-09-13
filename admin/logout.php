<?php

declare(strict_types=1);

require __DIR__.'/_init.php';
require_admin();
try{admin_post_guard();admin_service()->logout();flash('success','You have been signed out.');admin_redirect('login.php');}
catch(Throwable$exception){admin_flash_exception($exception);admin_redirect();}
