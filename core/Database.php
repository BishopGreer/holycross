<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function connect(?array $config = null): PDO
    {
        if (self::$pdo instanceof PDO && $config === null) {
            return self::$pdo;
        }

        $usingDefaultConfig = $config === null;
        $config ??= cms_config()['db'] ?? [];
        $host = $config['host'] ?? '127.0.0.1';
        $port = (int)($config['port'] ?? 3306);
        $database = $config['database'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

        $pdo = new PDO($dsn, (string)($config['username'] ?? ''), (string)($config['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        if ($usingDefaultConfig) {
            self::$pdo = $pdo;
        }

        return $pdo;
    }
}
