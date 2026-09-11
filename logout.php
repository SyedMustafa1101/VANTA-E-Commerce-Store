<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !verify_csrf(request_csrf_token())) {
    flash('error', 'The logout request could not be verified.');
    redirect('account/index.php');
}
auth_service()->logout();
flash('success', 'You are signed out. Your saved account bag remains intact.');
redirect('index.php');

