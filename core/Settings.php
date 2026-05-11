<?php

declare(strict_types=1);

final class Settings
{
    public static function get(string $name, string $default = ''): string
    {
        $stmt = Database::connect()->prepare('SELECT value FROM settings WHERE name = ? LIMIT 1');
        $stmt->execute([$name]);
        $value = $stmt->fetchColumn();

        return $value === false ? $default : (string)$value;
    }

    public static function set(string $name, string $value): void
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO settings (name, value, updated_at) VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()'
        );
        $stmt->execute([$name, $value]);
    }
}
