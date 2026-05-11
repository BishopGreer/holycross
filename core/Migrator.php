<?php

declare(strict_types=1);

final class Migrator
{
    public function __construct(private PDO $pdo)
    {
    }

    public function migrate(): array
    {
        $this->ensureMigrationTable();
        $applied = $this->appliedMigrations();
        $ran = [];

        foreach ($this->migrationFiles() as $file) {
            $name = basename($file);
            if (in_array($name, $applied, true)) {
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new RuntimeException("Unable to read migration: {$name}");
            }

            $this->pdo->exec($sql);
            $stmt = $this->pdo->prepare('INSERT INTO schema_migrations (migration, applied_at) VALUES (?, NOW())');
            $stmt->execute([$name]);
            $ran[] = $name;
        }

        return $ran;
    }

    private function ensureMigrationTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(190) NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function appliedMigrations(): array
    {
        return $this->pdo
            ->query('SELECT migration FROM schema_migrations ORDER BY migration')
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    private function migrationFiles(): array
    {
        $files = glob(CMS_ROOT . '/migrations/*.sql') ?: [];
        sort($files, SORT_NATURAL);

        return $files;
    }
}
