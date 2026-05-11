<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

$errors = [];
$done = false;

if (cms_is_installed()) {
    $done = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$done) {
    $db = [
        'host' => trim((string)($_POST['db_host'] ?? '127.0.0.1')),
        'port' => (int)($_POST['db_port'] ?? 3306),
        'database' => trim((string)($_POST['db_name'] ?? '')),
        'username' => trim((string)($_POST['db_user'] ?? '')),
        'password' => (string)($_POST['db_pass'] ?? ''),
        'charset' => 'utf8mb4',
    ];
    $appName = trim((string)($_POST['app_name'] ?? 'Holy Cross Parish and Friary'));
    $baseUrl = rtrim(trim((string)($_POST['base_url'] ?? '')), '/');
    $adminUser = trim((string)($_POST['admin_user'] ?? ''));
    $adminEmail = trim((string)($_POST['admin_email'] ?? ''));
    $adminPass = (string)($_POST['admin_pass'] ?? '');

    if ($db['database'] === '' || $db['username'] === '') {
        $errors[] = 'Database name and user are required.';
    }
    if ($adminUser === '' || $adminEmail === '' || strlen($adminPass) < 10) {
        $errors[] = 'Admin username, email, and a password of at least 10 characters are required.';
    }
    if (!is_writable(CMS_ROOT . '/config')) {
        $errors[] = 'The config directory must be writable by PHP during installation.';
    }

    if (!$errors) {
        try {
            $pdo = Database::connect($db);
            $ran = (new Migrator($pdo))->migrate();

            $stmt = $pdo->prepare(
                'INSERT INTO users (username, email, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, "admin", NOW(), NOW())'
            );
            $stmt->execute([$adminUser, $adminEmail, password_hash($adminPass, PASSWORD_DEFAULT)]);
            $adminId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO pages (title, slug, content, meta_description, status, created_by, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, "published", ?, ?, NOW(), NOW())'
            );
            $stmt->execute([
                'Home',
                'home',
                '<p>Welcome to Holy Cross Parish and Friary, an Old Catholic parish rooted in sacramental worship, Franciscan hospitality, and the life of prayer.</p><p>Use the admin backend to edit this page, publish ministry pages, and keep parish information current.</p>',
                'Holy Cross Parish and Friary in Houston, Texas.',
                $adminId,
                $adminId,
            ]);

            $config = [
                'app_name' => $appName ?: 'Holy Cross Parish and Friary',
                'base_url' => $baseUrl,
                'db' => $db,
                'session_name' => 'php_page_cms',
            ];

            $configPhp = "<?php\n\nreturn " . var_export($config, true) . ";\n";
            file_put_contents(cms_config_path(), $configPhp, LOCK_EX);
            $done = true;
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install Holy Cross CMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<main class="page">
    <div class="wrap">
        <h1>Install Holy Cross CMS</h1>
        <?php if ($done): ?>
            <div class="panel">
                <p><strong>Installation is complete.</strong></p>
                <p><a class="button" href="../admin/">Go to admin</a> <a class="button secondary" href="../">View site</a></p>
                <p class="muted">For production, delete or server-restrict the install directory.</p>
            </div>
        <?php else: ?>
            <?php foreach ($errors as $error): ?>
                <p class="notice error"><?= cms_e($error) ?></p>
            <?php endforeach; ?>
            <form method="post" class="panel">
                <h2>Site</h2>
                <label for="app_name">Site name</label>
                <input id="app_name" name="app_name" value="Holy Cross Parish and Friary">
                <label for="base_url">Base URL</label>
                <input id="base_url" name="base_url" placeholder="https://example.com">

                <h2>Database</h2>
                <label for="db_host">Host</label>
                <input id="db_host" name="db_host" value="127.0.0.1" required>
                <label for="db_port">Port</label>
                <input id="db_port" name="db_port" value="3306" required>
                <label for="db_name">Database</label>
                <input id="db_name" name="db_name" required>
                <label for="db_user">User</label>
                <input id="db_user" name="db_user" required>
                <label for="db_pass">Password</label>
                <input id="db_pass" name="db_pass" type="password">

                <h2>Admin</h2>
                <label for="admin_user">Username</label>
                <input id="admin_user" name="admin_user" required>
                <label for="admin_email">Email</label>
                <input id="admin_email" name="admin_email" type="email" required>
                <label for="admin_pass">Password</label>
                <input id="admin_pass" name="admin_pass" type="password" minlength="10" required>

                <p><button type="submit">Install</button></p>
            </form>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
