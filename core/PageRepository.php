<?php

declare(strict_types=1);

final class PageRepository
{
    public function all(): array
    {
        return Database::connect()
            ->query('SELECT * FROM pages ORDER BY updated_at DESC')
            ->fetchAll();
    }

    public function published(): array
    {
        return Database::connect()
            ->query('SELECT id, title, slug FROM pages WHERE status = "published" ORDER BY slug = "home" DESC, title ASC')
            ->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM pages WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $page = $stmt->fetch();

        return $page ?: null;
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM pages WHERE slug = ? AND status = "published" LIMIT 1');
        $stmt->execute([$slug]);
        $page = $stmt->fetch();

        return $page ?: null;
    }

    public function save(array $data, int $userId): int
    {
        $slug = $this->normalizeSlug((string)$data['slug']);
        $status = in_array($data['status'] ?? '', ['draft', 'published'], true) ? $data['status'] : 'draft';
        $id = (int)($data['id'] ?? 0);

        if ($id > 0) {
            $stmt = Database::connect()->prepare(
                'UPDATE pages SET title = ?, slug = ?, content = ?, meta_description = ?, status = ?, updated_by = ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->execute([
                trim((string)$data['title']),
                $slug,
                (string)$data['content'],
                trim((string)($data['meta_description'] ?? '')),
                $status,
                $userId,
                $id,
            ]);

            return $id;
        }

        $stmt = Database::connect()->prepare(
            'INSERT INTO pages (title, slug, content, meta_description, status, created_by, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([
            trim((string)$data['title']),
            $slug,
            (string)$data['content'],
            trim((string)($data['meta_description'] ?? '')),
            $status,
            $userId,
            $userId,
        ]);

        return (int)Database::connect()->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = Database::connect()->prepare('DELETE FROM pages WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9\/-]+/', '-', $slug) ?: '';
        $slug = preg_replace('#/{2,}#', '/', $slug) ?: '';
        $slug = trim($slug, '/-');

        return $slug === '' ? 'home' : $slug;
    }
}
