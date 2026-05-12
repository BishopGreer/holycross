<?php

declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';

cms_require_installation();

$repo = new PageRepository();
$page = $repo->findPublishedBySlug(cms_current_path_slug());
$navPages = $repo->published();
$config = cms_config();

if (!$page) {
    http_response_code(404);
    $page = [
        'title' => 'Page not found',
        'content' => '<p>The page you requested could not be found.</p>',
        'meta_description' => '',
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= cms_e($page['title']) ?> | <?= cms_e($config['app_name'] ?? 'Holy Cross Parish and Friary') ?></title>
    <?php if (!empty($page['meta_description'])): ?>
        <meta name="description" content="<?= cms_e($page['meta_description']) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= cms_e(cms_base_url('/assets/css/style.css')) ?>">
</head>
<body>
    <header class="site-header">
        <div class="parish-hero" role="img" aria-label="<?= cms_e($config['app_name'] ?? 'Holy Cross Parish and Friary') ?>"></div>
        <div class="site-nav">
            <nav class="primary-nav" aria-label="Primary">
                <?php foreach ($navPages as $navPage): ?>
                    <a href="<?= cms_e(cms_page_url((string)$navPage['slug'])) ?>">
                        <?= cms_e($navPage['title']) ?>
                    </a>
                <?php endforeach; ?>
                <a href="<?= cms_e(cms_base_url('/contact.php')) ?>">Contact</a>
                <a href="<?= cms_e(cms_base_url('/membership.php')) ?>">Membership</a>
            </nav>
        </div>
    </header>
    <main class="page public-page">
        <article class="wrap content-panel">
            <h1><?= cms_e($page['title']) ?></h1>
            <?= $page['content'] ?>
        </article>
    </main>
    <footer class="site-footer">
        <div class="wrap">
            <a class="footer-admin-link" href="<?= cms_e(cms_base_url('/admin/')) ?>">Admin</a>
            <p>Copyright 2026 - Holy Cross Parish and Friary</p>
        </div>
    </footer>
</body>
</html>
