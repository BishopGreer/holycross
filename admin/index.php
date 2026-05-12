<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

cms_require_installation();
Auth::requireLogin();

$repo = new PageRepository();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    Csrf::verify();
    $repo->delete((int)($_POST['id'] ?? 0));
    cms_redirect('/admin/');
}

$pages = $repo->all();
$title = 'Pages';
require __DIR__ . '/_header.php';
?>
<div class="toolbar">
    <h1>Pages</h1>
    <a class="button" href="<?= cms_e(cms_base_url('/admin/page.php')) ?>">New page</a>
</div>
<table>
    <thead>
        <tr>
            <th>Title</th>
            <th>Slug</th>
            <th>Parent</th>
            <th>Nav Order</th>
            <th>Status</th>
            <th>Updated</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pages as $page): ?>
            <tr>
                <td><?= cms_e($page['title']) ?></td>
                <td><a href="<?= cms_e(cms_page_url((string)$page['slug'])) ?>"><?= cms_e($page['slug']) ?></a></td>
                <td><?= cms_e($page['parent_title'] ?: '-') ?></td>
                <td><?= cms_e((string)($page['nav_order'] ?? 0)) ?></td>
                <td><?= cms_e($page['status']) ?></td>
                <td><?= cms_e($page['updated_at']) ?></td>
                <td>
                    <div class="actions">
                        <a class="button secondary" href="<?= cms_e(cms_base_url('/admin/page.php?id=' . $page['id'])) ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Delete this page?');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= cms_e((string)$page['id']) ?>">
                            <button class="danger" type="submit">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php require __DIR__ . '/_footer.php'; ?>
