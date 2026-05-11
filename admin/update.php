<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

cms_require_installation();
Auth::requireLogin();

$ran = null;
$error = '';
$latestVersion = null;
$githubMessage = '';
$githubResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    try {
        $action = (string)($_POST['action'] ?? 'run_migrations');

        if ($action === 'check_github') {
            $updater = new GitHubUpdater();
            $latestVersion = $updater->latestVersion();
            $githubMessage = $updater->hasUpdate($latestVersion)
                ? 'Version ' . $latestVersion . ' is available from GitHub.'
                : 'This site is already running the latest GitHub version.';
        }

        if ($action === 'install_github') {
            $updater = new GitHubUpdater();
            $latestVersion = $updater->latestVersion();

            if (!$updater->hasUpdate($latestVersion)) {
                $githubMessage = 'This site is already running the latest GitHub version.';
            } else {
                $githubResult = $updater->install($latestVersion);
                $githubMessage = 'Installed version ' . $latestVersion . ' from GitHub.';
            }
        }

        if ($action === 'run_migrations') {
            $ran = (new Migrator(Database::connect()))->migrate();
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$title = 'Update';
require __DIR__ . '/_header.php';
?>
<h1>Update</h1>
<div class="panel">
    <h2>GitHub Updates</h2>
    <p>Check the Holy Cross GitHub repository for a newer CMS release, then install it from the generated release ZIP.</p>
    <?php if ($error): ?>
        <p class="notice error"><?= cms_e($error) ?></p>
    <?php endif; ?>
    <?php if ($githubMessage): ?>
        <p class="notice"><?= cms_e($githubMessage) ?></p>
    <?php endif; ?>
    <?php if ($githubResult): ?>
        <p class="notice">
            Updated <?= cms_e((string)$githubResult['files']) ?> files.
            <?php if ($githubResult['migrations']): ?>
                Applied migrations: <?= cms_e(implode(', ', $githubResult['migrations'])) ?>.
            <?php else: ?>
                No pending database migrations.
            <?php endif; ?>
        </p>
    <?php endif; ?>
    <p class="muted">Installed version: <?= cms_e(CMS_VERSION) ?></p>
    <?php if ($latestVersion): ?>
        <p class="muted">Latest GitHub version: <?= cms_e($latestVersion) ?></p>
    <?php endif; ?>
    <form method="post" class="actions">
        <?= Csrf::field() ?>
        <button type="submit" name="action" value="check_github">Check GitHub</button>
        <button type="submit" name="action" value="install_github">Install GitHub update</button>
    </form>
</div>
<div class="panel">
    <h2>Database Updates</h2>
    <p>Run database migrations after adding new migration files or after installing a release.</p>
    <?php if (is_array($ran)): ?>
        <?php if ($ran): ?>
            <p class="notice">Applied: <?= cms_e(implode(', ', $ran)) ?></p>
        <?php else: ?>
            <p class="notice">No pending database updates.</p>
        <?php endif; ?>
    <?php endif; ?>
    <form method="post">
        <?= Csrf::field() ?>
        <button type="submit" name="action" value="run_migrations">Run database updates</button>
    </form>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
