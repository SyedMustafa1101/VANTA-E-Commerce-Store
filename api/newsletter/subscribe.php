<?php

declare(strict_types=1);

define('VANTA_API', true);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';

$input = require_post_json();
$email = normalize_email((string) ($input['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'message' => 'Enter a valid email address.'], 422);
}
$result = (new NewsletterRepository(db()))->subscribe($email);
$message = $result === 'existing'
    ? 'You are already on the list.'
    : 'You are on the list. Welcome after dark.';
json_response(['ok' => true, 'message' => $message]);

