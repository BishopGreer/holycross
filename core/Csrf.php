<?php

declare(strict_types=1);

final class Csrf
{
    public static function token(): string
    {
        cms_start_session();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string)$_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . cms_e(self::token()) . '">';
    }

    public static function verify(): void
    {
        cms_start_session();
        $sent = (string)($_POST['csrf_token'] ?? '');
        $known = (string)($_SESSION['csrf_token'] ?? '');

        if ($sent === '' || $known === '' || !hash_equals($known, $sent)) {
            http_response_code(419);
            exit('Security token mismatch.');
        }
    }
}

