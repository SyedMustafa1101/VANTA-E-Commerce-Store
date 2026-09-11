<?php

declare(strict_types=1);

// Copy this file to mail.php and edit only the ignored local copy.
// Use an app password or provider-specific SMTP credential, never your normal
// account password. Never commit production or personal SMTP credentials.
return [
    'enabled' => false,
    'host' => 'smtp.example.com',
    'port' => 587,
    'encryption' => 'tls', // tls (STARTTLS), ssl (SMTPS), or none for a local test server.
    'auth' => true,
    'username' => 'orders@example.com',
    'password' => '',
    'from_email' => 'orders@example.com',
    'from_name' => 'VANTA',
    'reply_to_email' => 'support@example.com',
    'reply_to_name' => 'VANTA Support',
    'timeout' => 15,
];
