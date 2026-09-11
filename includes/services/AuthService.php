<?php

declare(strict_types=1);

final class AuthService
{
    public function __construct(
        private UserRepository $users,
        private CartService $cart,
        private WishlistService $wishlist
    ) {
    }

    /** @param array<string, string> $input
     *  @return array<string, string>
     */
    public function registrationErrors(array $input): array
    {
        $errors = [];
        if (mb_strlen($input['first_name'] ?? '') < 2) $errors['first_name'] = 'Enter your first name.';
        if (mb_strlen($input['last_name'] ?? '') < 2) $errors['last_name'] = 'Enter your last name.';
        if (!filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
        if (strlen($input['password'] ?? '') < 10 || !preg_match('/[A-Za-z]/', $input['password'] ?? '') || !preg_match('/\d/', $input['password'] ?? '')) {
            $errors['password'] = 'Use at least 10 characters with a letter and a number.';
        }
        if (($input['password'] ?? '') !== ($input['confirm_password'] ?? '')) $errors['confirm_password'] = 'Passwords do not match.';
        return $errors;
    }

    /** @param array<string, string> $input */
    public function register(array $input): int
    {
        $email = normalize_email($input['email']);
        if ($this->users->findByEmail($email) !== null) {
            throw new DomainException('An account already uses that email address.');
        }
        return $this->users->create(
            trim($input['first_name']),
            trim($input['last_name']),
            $email,
            password_hash($input['password'], PASSWORD_DEFAULT)
        );
    }

    public function login(string $email, string $password): bool
    {
        $email = normalize_email($email);
        $ipHash = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'local'));
        if ($this->users->countRecentFailures($email, $ipHash) >= 8) {
            throw new DomainException('Too many login attempts. Try again in 15 minutes.');
        }
        $user = $this->users->findByEmail($email);
        $valid = $user !== null
            && $user['status'] === 'active'
            && password_verify($password, (string) $user['password_hash']);
        $this->users->recordLoginAttempt($email, $ipHash, $valid);
        if (!$valid) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['authenticated_at'] = time();
        $this->users->markLogin((int) $user['id']);
        $this->cart->mergeGuestIntoUser((int) $user['id']);
        $this->wishlist->mergeGuestIntoUser((int) $user['id']);
        reset_current_user_cache();
        return true;
    }

    public function logout(): void
    {
        unset($_SESSION['user_id'], $_SESSION['authenticated_at']);
        session_regenerate_id(true);
        reset_current_user_cache();
    }
}
