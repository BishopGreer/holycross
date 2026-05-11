<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

cms_require_installation();
Auth::requireLogin();

$ran = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();
    try {
        $ran = (new Migrator(Database::connect()))->migrate();
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$title = 'Update';
require __DIR__ . '/_header.php';
?>
<h1>Update</h1>
<div class="panel">
    <p>Run the updater after adding new files to the <code>migrations/</code> directory.</p>
    <?php if ($error): ?>
        <p class="notice error"><?= cms_e($error) ?></p>
    <?php endif; ?>
    <?php if (is_array($ran)): ?>
        <?php if ($ran): ?>
            <p class="notice">Applied: <?= cms_e(implode(', ', $ran)) ?></p>
        <?php else: ?>
            <p class="notice">No pending database updates.</p>
        <?php endif; ?>
    <?php endif; ?>
    <form method="post">
        <?= Csrf::field() ?>
        <button type="submit">Run updates</button>
    </form>
</div>
<?php require __DIR__ . '/_footer.php'; ?>

