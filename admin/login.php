<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

cms_require_installation();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (Auth::attempt((string)($_POST['username'] ?? ''), (string)($_POST['password'] ?? ''))) {
        cms_redirect('/admin/');
    }
    $error = 'Invalid username or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | <?= cms_e(cms_site_title()) ?></title>
    <link rel="stylesheet" href="<?= cms_e(cms_base_url('/assets/css/style.css')) ?>">
</head>
<body>
<main class="page">
    <div class="wrap">
        <h1>Admin Login</h1>
        <?php if ($error): ?>
            <p class="notice error"><?= cms_e($error) ?></p>
        <?php endif; ?>
        <form method="post" class="panel">
            <label for="username">Username</label>
            <input id="username" name="username" required>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>
            <p><button type="submit">Log in</button></p>
        </form>
    </div>
</main>
</body>
</html>
