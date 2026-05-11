<?php

declare(strict_types=1);

final class Auth
{
    public static function user(): ?array
    {
        cms_start_session();
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return null;
        }

        $stmt = Database::connect()->prepare('SELECT id, username, email, role FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$userId]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function attempt(string $username, string $password): bool
    {
        cms_start_session();
        $stmt = Database::connect()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        return true;
    }

    public static function logout(): void
    {
        cms_start_session();
        $_SESSION = [];
        session_destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::user()) {
            cms_redirect('/admin/login.php');
        }
    }
}
