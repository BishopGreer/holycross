<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

cms_require_installation();
Auth::requireLogin();

$repo = new PageRepository();
$mediaLibrary = new MediaLibrary();
$user = Auth::user();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$page = $id > 0 ? $repo->find($id) : null;
$errors = [];
$uploaded = false;

if ($id > 0 && !$page) {
    http_response_code(404);
    exit('Page not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    if (($_POST['action'] ?? '') === 'upload_image') {
        try {
            $mediaLibrary->upload($_FILES['media_file'] ?? []);
            $uploaded = true;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (($_POST['action'] ?? 'save_page') === 'save_page') {
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
}

$title = $page ? 'Edit Page' : 'New Page';
$mediaItems = $mediaLibrary->all();
$parentOptions = $repo->parentOptions((int)($page['id'] ?? 0));
require __DIR__ . '/_header.php';
?>
<div class="toolbar">
    <h1><?= cms_e($title) ?></h1>
    <a class="button secondary" href="<?= cms_e(cms_base_url('/admin/')) ?>">Back to pages</a>
</div>
<?php if (isset($_GET['saved'])): ?>
    <p class="notice">Page saved.</p>
<?php endif; ?>
<?php if ($uploaded): ?>
    <p class="notice">Image uploaded. Choose it below, place your cursor in the content box, then select Insert selected image.</p>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <p class="notice error"><?= cms_e($error) ?></p>
<?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="panel">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="upload_image">
    <input type="hidden" name="id" value="<?= cms_e((string)($page['id'] ?? 0)) ?>">

    <h2>Upload Image</h2>
    <label for="media_file">Image file</label>
    <input id="media_file" name="media_file" type="file" accept="image/jpeg,image/png,image/gif,image/webp" required>
    <p><button type="submit">Upload image</button></p>
</form>
<form method="post" class="panel">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="save_page">
    <input type="hidden" name="id" value="<?= cms_e((string)($page['id'] ?? 0)) ?>">

    <label for="title">Title</label>
    <input id="title" name="title" value="<?= cms_e($page['title'] ?? '') ?>" required>

    <label for="slug">Slug</label>
    <input id="slug" name="slug" value="<?= cms_e($page['slug'] ?? '') ?>" placeholder="about-us" required>

    <div class="settings-grid">
        <div>
            <label for="parent_id">Parent page</label>
            <select id="parent_id" name="parent_id">
                <?php $parentId = (int)($page['parent_id'] ?? 0); ?>
                <option value="0">No parent - top level</option>
                <?php foreach ($parentOptions as $option): ?>
                    <option value="<?= cms_e((string)$option['id']) ?>" <?= $parentId === (int)$option['id'] ? 'selected' : '' ?>>
                        <?= cms_e(str_repeat('- ', (int)$option['level']) . $option['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="nav_order">Navigation order</label>
            <input id="nav_order" name="nav_order" type="number" step="1" value="<?= cms_e((string)($page['nav_order'] ?? 0)) ?>">
        </div>
    </div>

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
        <button type="button" data-editor-action="image" title="Image" aria-label="Image">Image</button>
        <button type="button" data-editor-action="ul" title="Bulleted list" aria-label="Bulleted list">&bull; List</button>
        <button type="button" data-editor-action="ol" title="Numbered list" aria-label="Numbered list">1. List</button>
        <button type="button" data-editor-action="hr" title="Divider" aria-label="Divider">Line</button>
        <button type="button" data-editor-action="clear" title="Clear formatting from selection" aria-label="Clear formatting">Clear</button>
    </div>
    <div class="editor-media-picker">
        <label for="editor_image_url">Uploaded image</label>
        <select id="editor_image_url">
            <option value="">Choose an uploaded image</option>
            <?php foreach ($mediaItems as $item): ?>
                <option value="<?= cms_e($item['url']) ?>"><?= cms_e($item['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="editor_image_width">Image width</label>
        <select id="editor_image_width">
            <option value="100%">Full width</option>
            <option value="75%">Three quarters</option>
            <option value="50%">Half width</option>
            <option value="33%">One third</option>
            <option value="25%">One quarter</option>
        </select>
        <label for="editor_image_align">Position</label>
        <select id="editor_image_align">
            <option value="center">Center</option>
            <option value="left">Left</option>
            <option value="right">Right</option>
        </select>
        <label for="editor_image_caption">Caption</label>
        <input id="editor_image_caption" placeholder="Optional caption">
        <button type="button" class="editor-image-insert" data-editor-action="image">Insert selected image</button>
        <a class="button secondary" href="<?= cms_e(cms_base_url('/admin/media.php')) ?>">Upload images</a>
    </div>
    <textarea id="content" name="content" required><?= cms_e($page['content'] ?? '') ?></textarea>

    <section class="seo-fields">
        <h2>SEO Meta Information</h2>
        <label for="meta_title">Meta title</label>
        <input id="meta_title" name="meta_title" value="<?= cms_e($page['meta_title'] ?? '') ?>" maxlength="255" placeholder="<?= cms_e(($page['title'] ?? 'Page title') . ' | ' . cms_site_title()) ?>">

        <label for="meta_description">Meta description</label>
        <textarea id="meta_description" name="meta_description" maxlength="255"><?= cms_e($page['meta_description'] ?? '') ?></textarea>

        <label for="meta_keywords">Meta keywords</label>
        <input id="meta_keywords" name="meta_keywords" value="<?= cms_e($page['meta_keywords'] ?? '') ?>" maxlength="255" placeholder="Old Catholic, The Woodlands, parish, worship">
    </section>

    <p><button type="submit">Save page</button></p>
</form>
<script src="<?= cms_e(cms_base_url('/assets/js/editor.js')) ?>"></script>
<?php require __DIR__ . '/_footer.php'; ?>
