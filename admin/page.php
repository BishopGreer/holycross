<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

cms_require_installation();
Auth::requireLogin();

$repo = new PageRepository();
$user = Auth::user();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$page = $id > 0 ? $repo->find($id) : null;
$errors = [];

if ($id > 0 && !$page) {
    http_response_code(404);
    exit('Page not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();
    $titleValue = trim((string)($_POST['title'] ?? ''));
    $slugValue = trim((string)($_POST['slug'] ?? ''));
    $contentValue = trim((string)($_POST['content'] ?? ''));

    if ($titleValue === '') {
        $errors[] = 'Title is required.';
    }
    if ($slugValue === '') {
        $errors[] = 'Slug is required.';
    }
    if ($contentValue === '') {
        $errors[] = 'Content is required.';
    }

    if (!$errors) {
        try {
            $savedId = $repo->save($_POST, (int)$user['id']);
            cms_redirect('/admin/page.php?id=' . $savedId . '&saved=1');
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    $page = array_merge($page ?? [], $_POST);
}

$title = $page ? 'Edit Page' : 'New Page';
require __DIR__ . '/_header.php';
?>
<div class="toolbar">
    <h1><?= cms_e($title) ?></h1>
    <a class="button secondary" href="<?= cms_e(cms_base_url('/admin/')) ?>">Back to pages</a>
</div>
<?php if (isset($_GET['saved'])): ?>
    <p class="notice">Page saved.</p>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <p class="notice error"><?= cms_e($error) ?></p>
<?php endforeach; ?>
<form method="post" class="panel">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= cms_e((string)($page['id'] ?? 0)) ?>">

    <label for="title">Title</label>
    <input id="title" name="title" value="<?= cms_e($page['title'] ?? '') ?>" required>

    <label for="slug">Slug</label>
    <input id="slug" name="slug" value="<?= cms_e($page['slug'] ?? '') ?>" placeholder="about-us" required>

    <label for="meta_description">Meta description</label>
    <input id="meta_description" name="meta_description" value="<?= cms_e($page['meta_description'] ?? '') ?>" maxlength="255">

    <label for="status">Status</label>
    <select id="status" name="status">
        <?php $status = $page['status'] ?? 'draft'; ?>
        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
    </select>

    <label for="content">Content</label>
    <div class="editor-toolbar" role="toolbar" aria-label="Content formatting tools">
        <button type="button" data-editor-wrap="<h2>|</h2>" title="Heading 2" aria-label="Heading 2">H2</button>
        <button type="button" data-editor-wrap="<h3>|</h3>" title="Heading 3" aria-label="Heading 3">H3</button>
        <button type="button" data-editor-wrap="<p>|</p>" title="Paragraph" aria-label="Paragraph">P</button>
        <button type="button" data-editor-wrap="<strong>|</strong>" title="Bold" aria-label="Bold"><strong>B</strong></button>
        <button type="button" data-editor-wrap="<em>|</em>" title="Italic" aria-label="Italic"><em>I</em></button>
        <button type="button" data-editor-wrap="<blockquote>|</blockquote>" title="Block quote" aria-label="Block quote">&ldquo;</button>
        <button type="button" data-editor-action="link" title="Link" aria-label="Link">Link</button>
        <button type="button" data-editor-action="ul" title="Bulleted list" aria-label="Bulleted list">&bull; List</button>
        <button type="button" data-editor-action="ol" title="Numbered list" aria-label="Numbered list">1. List</button>
        <button type="button" data-editor-action="hr" title="Divider" aria-label="Divider">Line</button>
        <button type="button" data-editor-action="clear" title="Clear formatting from selection" aria-label="Clear formatting">Clear</button>
    </div>
    <textarea id="content" name="content" required><?= cms_e($page['content'] ?? '') ?></textarea>

    <p><button type="submit">Save page</button></p>
</form>
<script src="<?= cms_e(cms_base_url('/assets/js/editor.js')) ?>"></script>
<?php require __DIR__ . '/_footer.php'; ?>
