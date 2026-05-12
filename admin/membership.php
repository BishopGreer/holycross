<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

cms_require_installation();
Auth::requireLogin();

$repo = new MembershipRepository();
$errors = [];
$saved = false;
$viewId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'status') {
    Csrf::verify();

    try {
        $repo->updateStatus((int)($_POST['id'] ?? 0), (string)($_POST['status'] ?? 'new'));
        cms_redirect('/admin/membership.php?id=' . (int)($_POST['id'] ?? 0) . '&saved=1');
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

$application = $viewId > 0 ? $repo->find($viewId) : null;
$applications = $application ? [] : $repo->all();
$title = 'Membership';
require __DIR__ . '/_header.php';
?>
<div class="toolbar">
    <h1>Membership</h1>
    <?php if ($application): ?>
        <a class="button secondary" href="<?= cms_e(cms_base_url('/admin/membership.php')) ?>">Back to registrations</a>
    <?php endif; ?>
</div>
<?php if (isset($_GET['saved'])): ?>
    <p class="notice">Membership status updated.</p>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <p class="notice error"><?= cms_e($error) ?></p>
<?php endforeach; ?>

<?php if ($application): ?>
    <section class="panel">
        <h2><?= cms_e($application['household_name']) ?></h2>
        <form method="post" class="actions">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="status">
            <input type="hidden" name="id" value="<?= cms_e((string)$application['id']) ?>">
            <label class="inline-label" for="status">Status</label>
            <select id="status" name="status">
                <?php foreach (['new' => 'New', 'reviewed' => 'Reviewed', 'contacted' => 'Contacted', 'archived' => 'Archived'] as $value => $label): ?>
                    <option value="<?= cms_e($value) ?>" <?= $application['status'] === $value ? 'selected' : '' ?>><?= cms_e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Update status</button>
        </form>
    </section>
    <section class="panel">
        <h2>Primary Contact</h2>
        <dl class="detail-list">
            <dt>Name</dt><dd><?= cms_e($application['primary_name']) ?></dd>
            <dt>Name to use</dt><dd><?= cms_e($application['preferred_name'] ?: '-') ?></dd>
            <dt>Pronouns</dt><dd><?= cms_e($application['pronouns'] ?: '-') ?></dd>
            <dt>Gender identity</dt><dd><?= cms_e($application['gender_identity'] ?: '-') ?></dd>
            <dt>Date of birth</dt><dd><?= cms_e($application['date_of_birth'] ?: '-') ?></dd>
            <dt>Email</dt><dd><a href="mailto:<?= cms_e($application['email']) ?>"><?= cms_e($application['email']) ?></a></dd>
            <dt>Phone</dt><dd><?= cms_e($application['phone'] ?: '-') ?></dd>
            <dt>Preferred contact</dt><dd><?= cms_e($application['preferred_contact'] ?: '-') ?></dd>
        </dl>
    </section>
    <section class="panel">
        <h2>Address</h2>
        <p>
            <?= cms_e($application['address_line1']) ?><br>
            <?php if ($application['address_line2']): ?><?= cms_e($application['address_line2']) ?><br><?php endif; ?>
            <?= cms_e($application['city']) ?>, <?= cms_e($application['state']) ?> <?= cms_e($application['postal_code']) ?>
        </p>
    </section>
    <section class="panel">
        <h2>Church Life</h2>
        <dl class="detail-list">
            <dt>Faith community</dt><dd><?= cms_e($application['current_church'] ?: '-') ?></dd>
            <dt>Baptism status</dt><dd><?= cms_e($application['baptism_status'] ?: '-') ?></dd>
            <dt>Sacraments and milestones</dt><dd><?= nl2br(cms_e($application['sacraments_received'] ?: '-')) ?></dd>
            <dt>Ministry interests</dt><dd><?= nl2br(cms_e($application['ministries_interest'] ?: '-')) ?></dd>
            <dt>Pastoral notes</dt><dd><?= nl2br(cms_e($application['pastoral_notes'] ?: '-')) ?></dd>
            <dt>Access needs</dt><dd><?= nl2br(cms_e($application['accessibility_needs'] ?: '-')) ?></dd>
        </dl>
    </section>
    <section class="panel">
        <h2>Additional Household Members</h2>
        <?php if (!$application['members']): ?>
            <p>No additional household members were listed.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Name to use</th>
                        <th>Pronouns</th>
                        <th>Gender identity</th>
                        <th>Date of birth</th>
                        <th>Relationship</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($application['members'] as $member): ?>
                        <tr>
                            <td><?= cms_e($member['name']) ?></td>
                            <td><?= cms_e($member['preferred_name'] ?: '-') ?></td>
                            <td><?= cms_e($member['pronouns'] ?: '-') ?></td>
                            <td><?= cms_e($member['gender_identity'] ?: '-') ?></td>
                            <td><?= cms_e($member['date_of_birth'] ?: '-') ?></td>
                            <td><?= cms_e($member['relationship']) ?></td>
                            <td><?= nl2br(cms_e($member['notes'] ?: '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Household</th>
                <th>Primary Contact</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Submitted</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($applications as $item): ?>
                <tr>
                    <td><?= cms_e($item['household_name']) ?></td>
                    <td><?= cms_e($item['preferred_name'] ?: $item['primary_name']) ?></td>
                    <td><a href="mailto:<?= cms_e($item['email']) ?>"><?= cms_e($item['email']) ?></a></td>
                    <td><?= cms_e($item['phone'] ?: '-') ?></td>
                    <td><?= cms_e($item['status']) ?></td>
                    <td><?= cms_e($item['created_at']) ?></td>
                    <td><a class="button secondary" href="<?= cms_e(cms_base_url('/admin/membership.php?id=' . $item['id'])) ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>
