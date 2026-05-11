<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

cms_require_installation();
Auth::requireLogin();

$mediaLibrary = new MediaLibrary();
$errors = [];
$uploaded = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    try {
        $mediaLibrary->upload($_FILES['media_file'] ?? []);
        $uploaded = true;
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

$mediaItems = $mediaLibrary->all();
$title = 'Media';
require __DIR__ . '/_header.php';
?>
<div class="toolbar">
    <h1>Media</h1>
    <a class="button secondary" href="<?= cms_e(cms_base_url('/admin/')) ?>">Back to pages</a>
</div>
<?php if ($uploaded): ?>
    <p class="notice">Image uploaded.</p>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <p class="notice error"><?= cms_e($error) ?></p>
<?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="panel">
    <?= Csrf::field() ?>
    <h2>Upload Image</h2>
    <label for="media_file">Image file</label>
    <input id="media_file" name="media_file" type="file" accept="image/jpeg,image/png,image/gif,image/webp" required>
    <p><button type="submit">Upload image</button></p>
</form>
<section class="panel">
    <h2>Uploaded Files</h2>
    <?php if (!$mediaItems): ?>
        <p class="muted">No images have been uploaded yet.</p>
    <?php else: ?>
        <div class="media-grid">
            <?php foreach ($mediaItems as $item): ?>
                <article class="media-item">
                    <a href="<?= cms_e($item['url']) ?>" target="_blank" rel="noopener">
                        <img src="<?= cms_e($item['url']) ?>" alt="">
                    </a>
                    <div>
                        <strong><?= cms_e($item['name']) ?></strong>
                        <p class="muted"><?= cms_e(number_format($item['size'] / 1024, 1)) ?> KB · <?= cms_e($item['modified']) ?></p>
                        <input readonly value="<?= cms_e($item['url']) ?>" onclick="this.select();">
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
