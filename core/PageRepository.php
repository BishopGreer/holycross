<?php

declare(strict_types=1);

final class PageRepository
{
    public function all(): array
    {
        return Database::connect()
            ->query(
                'SELECT p.*, parent.title AS parent_title
                 FROM pages p
                 LEFT JOIN pages parent ON parent.id = p.parent_id
                 ORDER BY p.parent_id IS NOT NULL, COALESCE(parent.title, p.title), p.nav_order ASC, p.title ASC'
            )
            ->fetchAll();
    }

    public function published(): array
    {
        return Database::connect()
            ->query(
                'SELECT id, parent_id, title, slug, nav_order
                 FROM pages
                 WHERE status = "published"
                 ORDER BY parent_id IS NOT NULL, nav_order ASC, slug = "home" DESC, title ASC'
            )
            ->fetchAll();
    }

    public function parentOptions(int $excludeId = 0): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT id, parent_id, title, slug
             FROM pages
             WHERE id <> ?
             ORDER BY parent_id IS NOT NULL, title ASC'
        );
        $stmt->execute([$excludeId]);

        return $this->flattenForSelect($this->buildTree($stmt->fetchAll()));
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
        $parentId = (int)($data['parent_id'] ?? 0);
        $parentId = $parentId > 0 ? $parentId : null;
        $navOrder = (int)($data['nav_order'] ?? 0);

        if ($id > 0 && $parentId === $id) {
            throw new InvalidArgumentException('A page cannot be nested under itself.');
        }

        if ($id > 0 && $parentId !== null && $this->isDescendant($parentId, $id)) {
            throw new InvalidArgumentException('A page cannot be nested under one of its own child pages.');
        }

        if ($id > 0) {
            $stmt = Database::connect()->prepare(
                'UPDATE pages SET title = ?, slug = ?, parent_id = ?, content = ?, meta_title = ?, meta_description = ?, meta_keywords = ?, status = ?, nav_order = ?, updated_by = ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->execute([
                trim((string)$data['title']),
                $slug,
                $parentId,
                (string)$data['content'],
                trim((string)($data['meta_title'] ?? '')),
                trim((string)($data['meta_description'] ?? '')),
                trim((string)($data['meta_keywords'] ?? '')),
                $status,
                $navOrder,
                $userId,
                $id,
            ]);

            return $id;
        }

        $stmt = Database::connect()->prepare(
            'INSERT INTO pages (title, slug, parent_id, content, meta_title, meta_description, meta_keywords, status, nav_order, created_by, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([
            trim((string)$data['title']),
            $slug,
            $parentId,
            (string)$data['content'],
            trim((string)($data['meta_title'] ?? '')),
            trim((string)($data['meta_description'] ?? '')),
            trim((string)($data['meta_keywords'] ?? '')),
            $status,
            $navOrder,
            $userId,
            $userId,
        ]);

        return (int)Database::connect()->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = Database::connect()->prepare('UPDATE pages SET parent_id = NULL WHERE parent_id = ?');
        $stmt->execute([$id]);

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

    private function isDescendant(int $possibleDescendantId, int $pageId): bool
    {
        $currentId = $possibleDescendantId;
        $seen = [];

        while ($currentId > 0 && !in_array($currentId, $seen, true)) {
            if ($currentId === $pageId) {
                return true;
            }

            $seen[] = $currentId;
            $stmt = Database::connect()->prepare('SELECT parent_id FROM pages WHERE id = ? LIMIT 1');
            $stmt->execute([$currentId]);
            $currentId = (int)($stmt->fetchColumn() ?: 0);
        }

        return false;
    }

    private function buildTree(array $pages): array
    {
        $byId = [];
        $tree = [];

        foreach ($pages as $page) {
            $page['children'] = [];
            $byId[(int)$page['id']] = $page;
        }

        foreach ($byId as $id => &$page) {
            $parentId = (int)($page['parent_id'] ?? 0);
            if ($parentId > 0 && isset($byId[$parentId])) {
                $byId[$parentId]['children'][] = &$page;
                continue;
            }

            $tree[] = &$page;
        }
        unset($page);

        return $tree;
    }

    private function flattenForSelect(array $pages, int $level = 0): array
    {
        $flat = [];

        foreach ($pages as $page) {
            $page['level'] = $level;
            $flat[] = $page;

            if (!empty($page['children'])) {
                $flat = array_merge($flat, $this->flattenForSelect($page['children'], $level + 1));
            }
        }

        return $flat;
    }
}
