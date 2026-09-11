<?php

declare(strict_types=1);

define('VANTA_API', true);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';

$input = require_post_json();
$userId = current_user_id();
if ($userId === null) {
    json_response(['ok' => false, 'message' => 'Sign in to submit a review.'], 401);
}
$productId = (int) ($input['product_id'] ?? 0);
$rating = filter_var($input['rating'] ?? null, FILTER_VALIDATE_INT);
$content = trim((string) ($input['content'] ?? ''));
if ($rating === false || $rating < 1 || $rating > 5) {
    json_response(['ok' => false, 'message' => 'Choose a rating from 1 to 5.'], 422);
}
if (mb_strlen($content) < 20 || mb_strlen($content) > 1500) {
    json_response(['ok' => false, 'message' => 'Write between 20 and 1,500 characters.'], 422);
}
$reviews = new ReviewRepository(db());
if (setting('review_requires_purchase', '1') === '1' && !$reviews->hasPurchased($userId, $productId)) {
    json_response(['ok' => false, 'message' => 'Reviews are available after purchasing this piece.'], 403);
}
try {
    $reviews->create($userId, $productId, (int) $rating, $content);
    json_response(['ok' => true, 'message' => 'Review submitted for moderation.']);
} catch (PDOException $exception) {
    if ((string) $exception->getCode() === '23000') {
        json_response(['ok' => false, 'message' => 'You have already reviewed this piece.'], 409);
    }
    throw $exception;
}

