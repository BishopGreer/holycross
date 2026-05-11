<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

cms_require_installation();
Auth::requireLogin();

$errors = [];
$created = false;
$updated = false;
$editingUser = null;
$editingId = (int)($_GET['edit'] ?? $_POST['id'] ?? 0);

if ($editingId > 0) {
    $stmt = Database::connect()->prepare('SELECT id, username, display_name, email, role, created_at, updated_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$editingId]);
    $editingUser = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    if (($_POST['action'] ?? '') === 'create_admin') {
        $newUsername = trim((string)($_POST['admin_username'] ?? ''));
        $newDisplayName = trim((string)($_POST['admin_display_name'] ?? ''));
        $newEmail = trim((string)($_POST['admin_email'] ?? ''));
        $newPassword = (string)($_POST['admin_password'] ?? '');
        $newPasswordConfirm = (string)($_POST['admin_password_confirm'] ?? '');

        if ($newUsername === '') {
            $errors[] = 'Admin username is required.';
        }
        if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid admin email address is required.';
        }
        if (strlen($newPassword) < 10) {
            $errors[] = 'Admin password must be at least 10 characters.';
        }
        if ($newPassword !== $newPasswordConfirm) {
            $errors[] = 'Admin password confirmation does not match.';
        }

        if (!$errors) {
            $stmt = Database::connect()->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$newUsername, $newEmail]);

            if ((int)$stmt->fetchColumn() > 0) {
                $errors[] = 'That username or email is already in use.';
            }
        }

        if (!$errors) {
            $stmt = Database::connect()->prepare(
                'INSERT INTO users (username, display_name, email, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, ?, "admin", NOW(), NOW())'
            );
            $stmt->execute([$newUsername, $newDisplayName ?: null, $newEmail, password_hash($newPassword, PASSWORD_DEFAULT)]);
            $created = true;
        }
    }

    if (($_POST['action'] ?? '') === 'update_user') {
        $userId = (int)($_POST['id'] ?? 0);
        if ($editingId !== $userId) {
            $editingId = $userId;
            $stmt = Database::connect()->prepare('SELECT id, username, display_name, email, role, created_at, updated_at FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$editingId]);
            $editingUser = $stmt->fetch() ?: null;
        }

        $displayName = trim((string)($_POST['display_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

        if (!$editingUser) {
            $errors[] = 'User not found.';
        } else {
            $editingUser = array_merge($editingUser, [
                'display_name' => $displayName,
                'email' => $email,
            ]);
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if ($password !== '' && strlen($password) < 10) {
            $errors[] = 'Password must be at least 10 characters.';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'Password confirmation does not match.';
        }

        if (!$errors) {
            $stmt = Database::connect()->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?');
            $stmt->execute([$email, $userId]);

            if ((int)$stmt->fetchColumn() > 0) {
                $errors[] = 'That email address is already in use.';
            }
        }

        if (!$errors) {
            if ($password !== '') {
                $stmt = Database::connect()->prepare(
                    'UPDATE users SET display_name = ?, email = ?, password_hash = ?, updated_at = NOW() WHERE id = ?'
                );
                $stmt->execute([$displayName ?: null, $email, password_hash($password, PASSWORD_DEFAULT), $userId]);
            } else {
                $stmt = Database::connect()->prepare(
                    'UPDATE users SET display_name = ?, email = ?, updated_at = NOW() WHERE id = ?'
                );
                $stmt->execute([$displayName ?: null, $email, $userId]);
            }

            $updated = true;
            $stmt = Database::connect()->prepare('SELECT id, username, display_name, email, role, created_at, updated_at FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $editingUser = $stmt->fetch() ?: null;
        }
    }
}

$users = Database::connect()
    ->query('SELECT id, username, display_name, email, role, created_at, updated_at FROM users ORDER BY username ASC')
    ->fetchAll();

$title = 'Users';
require __DIR__ . '/_header.php';
?>
<div class="toolbar">
    <h1>Users</h1>
    <a class="button secondary" href="<?= cms_e(cms_base_url('/admin/')) ?>">Back to pages</a>
</div>
<?php if ($created): ?>
    <p class="notice">Admin user created.</p>
<?php endif; ?>
<?php if ($updated): ?>
    <p class="notice">User updated.</p>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <p class="notice error"><?= cms_e($error) ?></p>
<?php endforeach; ?>
<section class="panel">
    <h2>Current Users</h2>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Known as</th>
                <th>Email</th>
                <th>Role</th>
                <th>Updated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= cms_e($user['username']) ?></td>
                    <td><?= cms_e($user['display_name'] ?: $user['username']) ?></td>
                    <td><?= cms_e($user['email']) ?></td>
                    <td><?= cms_e($user['role']) ?></td>
                    <td><?= cms_e($user['updated_at']) ?></td>
                    <td><a class="button secondary" href="<?= cms_e(cms_base_url('/admin/users.php?edit=' . $user['id'])) ?>">Edit</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php if ($editingUser): ?>
    <form method="post" class="panel">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="update_user">
        <input type="hidden" name="id" value="<?= cms_e((string)$editingUser['id']) ?>">

        <h2>Edit <?= cms_e($editingUser['username']) ?></h2>
        <label for="display_name">Known as</label>
        <input id="display_name" name="display_name" value="<?= cms_e($editingUser['display_name'] ?? '') ?>">

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="<?= cms_e($editingUser['email']) ?>" required>

        <div class="settings-grid">
            <div>
                <label for="password">New password</label>
                <input id="password" name="password" type="password" minlength="10">
            </div>
            <div>
                <label for="password_confirm">Confirm new password</label>
                <input id="password_confirm" name="password_confirm" type="password" minlength="10">
            </div>
        </div>

        <p class="actions">
            <button type="submit">Save user</button>
            <a class="button secondary" href="<?= cms_e(cms_base_url('/admin/users.php')) ?>">Cancel</a>
        </p>
    </form>
<?php endif; ?>
<form method="post" class="panel">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="create_admin">

    <h2>Add Admin User</h2>
    <label for="admin_username">Username</label>
    <input id="admin_username" name="admin_username" required>

    <label for="admin_display_name">Known as</label>
    <input id="admin_display_name" name="admin_display_name">

    <label for="admin_email">Email</label>
    <input id="admin_email" name="admin_email" type="email" required>

    <div class="settings-grid">
        <div>
            <label for="admin_password">Password</label>
            <input id="admin_password" name="admin_password" type="password" minlength="10" required>
        </div>
        <div>
            <label for="admin_password_confirm">Confirm password</label>
            <input id="admin_password_confirm" name="admin_password_confirm" type="password" minlength="10" required>
        </div>
    </div>

    <p><button type="submit">Create admin user</button></p>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
