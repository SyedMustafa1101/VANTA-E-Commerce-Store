<?php

declare(strict_types=1);

final class NewsletterRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return 'created'|'existing'|'resubscribed' */
    public function subscribe(string $email): string
    {
        $statement = $this->pdo->prepare(
            'SELECT id, status FROM newsletter_subscribers WHERE email = ? LIMIT 1'
        );
        $statement->execute([$email]);
        $subscriber = $statement->fetch();
        if (!$subscriber) {
            $insert = $this->pdo->prepare(
                'INSERT INTO newsletter_subscribers (email, status) VALUES (?, \'subscribed\')'
            );
            $insert->execute([$email]);
            return 'created';
        }
        if ($subscriber['status'] === 'subscribed') {
            return 'existing';
        }
        $update = $this->pdo->prepare(
            'UPDATE newsletter_subscribers SET status = \'subscribed\' WHERE id = ?'
        );
        $update->execute([(int) $subscriber['id']]);
        return 'resubscribed';
    }
}
