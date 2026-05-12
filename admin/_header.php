<?php

$config = cms_config();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= cms_e($title ?? 'Admin') ?> | <?= cms_e(cms_site_title()) ?></title>
    <link rel="stylesheet" href="<?= cms_e(cms_base_url('/assets/css/style.css')) ?>">
</head>
<body>
    <header class="admin-bar">
        <div class="wrap">
            <a class="admin-brand" href="<?= cms_e(cms_base_url('/admin/')) ?>">
                <img src="<?= cms_e(cms_base_url('/assets/images/holy-cross-logo.png')) ?>" alt="">
                <strong><?= cms_e($config['app_name'] ?? cms_site_title()) ?> Admin</strong>
            </a>
            <nav aria-label="Admin">
                <a href="<?= cms_e(cms_base_url('/admin/')) ?>">Pages</a>
                <a href="<?= cms_e(cms_base_url('/admin/media.php')) ?>">Media</a>
                <a href="<?= cms_e(cms_base_url('/admin/membership.php')) ?>">Membership</a>
                <a href="<?= cms_e(cms_base_url('/admin/users.php')) ?>">Users</a>
                <a href="<?= cms_e(cms_base_url('/admin/settings.php')) ?>">Settings</a>
                <a href="<?= cms_e(cms_base_url('/admin/update.php')) ?>">Update</a>
                <a href="<?= cms_e(cms_base_url('/')) ?>">View site</a>
                <a href="<?= cms_e(cms_base_url('/admin/logout.php')) ?>">Log out</a>
            </nav>
        </div>
    </header>
    <main class="page">
        <div class="wrap">
