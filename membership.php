<?php

declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';

cms_require_installation();
cms_start_session();

$config = cms_config();
$repo = new PageRepository();
$navPages = $repo->published();
$membershipRepo = new MembershipRepository();
$recipient = Settings::get('membership_recipient_email') ?: Settings::get('contact_recipient_email');
$errors = [];
$sent = false;
$form = [
    'household_name' => '',
    'primary_name' => '',
    'preferred_name' => '',
    'pronouns' => '',
    'gender_identity' => '',
    'date_of_birth' => '',
    'email' => '',
    'phone' => '',
    'address_line1' => '',
    'address_line2' => '',
    'city' => '',
    'state' => '',
    'postal_code' => '',
    'preferred_contact' => '',
    'current_church' => '',
    'baptism_status' => '',
    'sacraments_received' => '',
    'ministries_interest' => '',
    'pastoral_notes' => '',
    'accessibility_needs' => '',
    'consent_to_contact' => false,
];
$members = [];

function membership_field_rows(array $labels, array $values): string
{
    $rows = '';

    foreach ($labels as $key => $label) {
        $value = trim((string)($values[$key] ?? ''));
        if ($value === '') {
            continue;
        }

        $rows .= '<tr><td style="padding:10px;border-bottom:1px solid #d9cbb5;font-weight:bold;color:#603a17;width:34%;">'
            . cms_e($label)
            . '</td><td style="padding:10px;border-bottom:1px solid #d9cbb5;">'
            . nl2br(cms_e($value))
            . '</td></tr>';
    }

    return $rows;
}

function membership_email_template(string $heading, string $intro, array $form, array $members): string
{
    $siteName = (string)(cms_config()['app_name'] ?? 'Holy Cross Parish and Friary');
    $labels = [
        'household_name' => 'Household',
        'primary_name' => 'Primary contact',
        'preferred_name' => 'Name to use',
        'pronouns' => 'Pronouns',
        'gender_identity' => 'Gender identity',
        'date_of_birth' => 'Date of birth',
        'email' => 'Email',
        'phone' => 'Phone',
        'address_line1' => 'Address',
        'address_line2' => 'Address line 2',
        'city' => 'City',
        'state' => 'State',
        'postal_code' => 'Postal code',
        'preferred_contact' => 'Preferred contact',
        'current_church' => 'Current or previous faith community',
        'baptism_status' => 'Baptism status',
        'sacraments_received' => 'Sacraments and milestones',
        'ministries_interest' => 'Ministry interests',
        'pastoral_notes' => 'Pastoral notes',
        'accessibility_needs' => 'Accessibility needs',
    ];
    $memberRows = '';

    foreach ($members as $member) {
        $memberDetails = [];
        foreach (['preferred_name', 'pronouns', 'gender_identity', 'date_of_birth', 'relationship', 'notes'] as $key) {
            $value = trim((string)($member[$key] ?? ''));
            if ($value !== '') {
                $memberDetails[] = cms_e(str_replace('_', ' ', ucfirst($key))) . ': ' . cms_e($value);
            }
        }

        $memberRows .= '<tr><td style="padding:10px;border-bottom:1px solid #d9cbb5;font-weight:bold;color:#603a17;width:34%;">'
            . cms_e($member['name'])
            . '</td><td style="padding:10px;border-bottom:1px solid #d9cbb5;">'
            . implode('<br>', $memberDetails)
            . '</td></tr>';
    }

    if ($memberRows === '') {
        $memberRows = '<tr><td colspan="2" style="padding:10px;border-bottom:1px solid #d9cbb5;">No additional household members were listed.</td></tr>';
    }

    return '<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>' . cms_e($heading) . '</title>
</head>
<body style="margin:0;background:#f7f1e5;color:#25170c;font-family:Georgia,Times New Roman,serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f7f1e5;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:760px;background:#fffdf8;border:1px solid #d9cbb5;border-top:8px solid #be202e;">
                    <tr>
                        <td style="padding:28px 28px 10px;text-align:center;background:#603a17;color:#ffffff;">
                            <div style="font-family:American Typewriter,Courier New,serif;font-size:24px;font-weight:bold;">' . cms_e($siteName) . '</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <h1 style="margin:0 0 14px;color:#be202e;font-family:American Typewriter,Courier New,serif;font-size:28px;line-height:1.2;">' . cms_e($heading) . '</h1>
                            <p style="margin:0 0 20px;font-size:17px;line-height:1.6;">' . cms_e($intro) . '</p>
                            <h2 style="margin:0 0 10px;color:#603a17;font-family:American Typewriter,Courier New,serif;font-size:22px;">Household Information</h2>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin:0 0 24px;">' . membership_field_rows($labels, $form) . '</table>
                            <h2 style="margin:0 0 10px;color:#603a17;font-family:American Typewriter,Courier New,serif;font-size:22px;">Additional Household Members</h2>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">' . $memberRows . '</table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

function membership_posted_members(): array
{
    $posted = $_POST['members'] ?? [];
    if (!is_array($posted)) {
        return [];
    }

    $members = [];
    foreach ($posted as $member) {
        if (!is_array($member)) {
            continue;
        }

        $clean = [
            'name' => trim((string)($member['name'] ?? '')),
            'preferred_name' => trim((string)($member['preferred_name'] ?? '')),
            'pronouns' => trim((string)($member['pronouns'] ?? '')),
            'gender_identity' => trim((string)($member['gender_identity'] ?? '')),
            'date_of_birth' => trim((string)($member['date_of_birth'] ?? '')),
            'relationship' => trim((string)($member['relationship'] ?? '')),
            'notes' => trim((string)($member['notes'] ?? '')),
        ];

        if (implode('', $clean) === '') {
            continue;
        }

        $members[] = $clean;
    }

    return $members;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    foreach (array_keys($form) as $key) {
        if ($key === 'consent_to_contact') {
            $form[$key] = isset($_POST[$key]);
            continue;
        }

        $form[$key] = trim((string)($_POST[$key] ?? ''));
    }
    $members = membership_posted_members();

    if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'The membership form recipient has not been configured yet.';
    }
    foreach (['household_name', 'primary_name', 'email', 'address_line1', 'city', 'state', 'postal_code'] as $required) {
        if ($form[$required] === '') {
            $errors[] = ucfirst(str_replace('_', ' ', $required)) . ' is required.';
        }
    }
    if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (!$form['consent_to_contact']) {
        $errors[] = 'Please confirm we may contact you about membership.';
    }
    foreach ($members as $index => $member) {
        if ($member['name'] === '' || $member['relationship'] === '') {
            $errors[] = 'Each additional household member needs a name and relationship.';
            break;
        }
        if ($member['date_of_birth'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $member['date_of_birth'])) {
            $errors[] = 'Household member ' . ($index + 1) . ' has an invalid date of birth.';
            break;
        }
    }
    [$captchaOk, $captchaError] = HCaptcha::verify(
        (string)($_POST['h-captcha-response'] ?? ''),
        (string)($_SERVER['REMOTE_ADDR'] ?? '')
    );
    if (!$captchaOk) {
        $errors[] = $captchaError;
    }

    if (!$errors) {
        try {
            $membershipRepo->create($form, $members);
            $siteName = (string)($config['app_name'] ?? 'Holy Cross Parish and Friary');
            $adminHtml = membership_email_template('New Parish Membership Registration', 'A new membership registration was submitted through the parish website.', $form, $members);
            $copyHtml = membership_email_template('Your Membership Registration Was Received', 'Thank you for reaching out to Holy Cross Parish and Friary. A copy of your registration is below for your records.', $form, $members);
            [$sentToRecipient, $recipientError] = Mailer::send($recipient, $siteName . ': Membership registration from ' . $form['primary_name'], $adminHtml, $form['email']);
            [$sentCopy, $copyError] = Mailer::send($form['email'], 'Copy of your membership registration to ' . $siteName, $copyHtml, $recipient);

            if ($sentToRecipient && $sentCopy) {
                $sent = true;
                foreach (array_keys($form) as $key) {
                    $form[$key] = $key === 'consent_to_contact' ? false : '';
                }
                $members = [];
            } else {
                $errors[] = 'Your registration was saved, but the email could not be sent. ' . ($recipientError ?: $copyError ?: 'Please check the mail settings.');
            }
        } catch (Throwable $e) {
            $errors[] = 'The registration could not be saved. ' . $e->getMessage();
        }
    }
}

$displayMembers = $members ?: [[
    'name' => '',
    'preferred_name' => '',
    'pronouns' => '',
    'gender_identity' => '',
    'date_of_birth' => '',
    'relationship' => '',
    'notes' => '',
]];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Membership | <?= cms_e($config['app_name'] ?? 'Holy Cross Parish and Friary') ?></title>
    <meta name="description" content="Register for parish membership at Holy Cross Parish and Friary.">
    <link rel="stylesheet" href="<?= cms_e(cms_base_url('/assets/css/style.css')) ?>">
    <?= HCaptcha::scriptTag() ?>
</head>
<body>
    <header class="site-header">
        <div class="parish-hero" role="img" aria-label="<?= cms_e($config['app_name'] ?? 'Holy Cross Parish and Friary') ?>"></div>
        <div class="site-nav">
            <nav class="primary-nav" aria-label="Primary">
                <?= cms_public_nav($navPages) ?>
            </nav>
        </div>
    </header>
    <main class="page public-page">
        <article class="wrap content-panel">
            <h1>Parish Membership</h1>
            <?php if ($sent): ?>
                <p class="notice">Thank you. Your membership registration has been received, and a copy has been emailed to you.</p>
            <?php endif; ?>
            <?php foreach ($errors as $error): ?>
                <p class="notice error"><?= cms_e($error) ?></p>
            <?php endforeach; ?>
            <form method="post" class="contact-form membership-form">
                <?= Csrf::field() ?>

                <h2>Household</h2>
                <label for="household_name">Household or family name</label>
                <input id="household_name" name="household_name" value="<?= cms_e((string)$form['household_name']) ?>" required>

                <label for="primary_name">Primary contact name</label>
                <input id="primary_name" name="primary_name" value="<?= cms_e((string)$form['primary_name']) ?>" required>

                <label for="preferred_name">Name you would like us to use</label>
                <input id="preferred_name" name="preferred_name" value="<?= cms_e((string)$form['preferred_name']) ?>">

                <div class="settings-grid">
                    <div>
                        <label for="pronouns">Pronouns</label>
                        <input id="pronouns" name="pronouns" value="<?= cms_e((string)$form['pronouns']) ?>" placeholder="she/her, he/him, they/them, your words">
                    </div>
                    <div>
                        <label for="gender_identity">Gender identity</label>
                        <input id="gender_identity" name="gender_identity" value="<?= cms_e((string)$form['gender_identity']) ?>" placeholder="Optional">
                    </div>
                </div>

                <div class="settings-grid">
                    <div>
                        <label for="date_of_birth">Date of birth</label>
                        <input id="date_of_birth" name="date_of_birth" type="date" value="<?= cms_e((string)$form['date_of_birth']) ?>">
                    </div>
                    <div>
                        <label for="preferred_contact">Preferred contact method</label>
                        <select id="preferred_contact" name="preferred_contact">
                            <?php foreach (['' => 'Choose one', 'email' => 'Email', 'phone' => 'Phone', 'text' => 'Text message', 'mail' => 'Postal mail'] as $value => $label): ?>
                                <option value="<?= cms_e($value) ?>" <?= $form['preferred_contact'] === $value ? 'selected' : '' ?>><?= cms_e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label for="email">Email address</label>
                <input id="email" name="email" type="email" value="<?= cms_e((string)$form['email']) ?>" required>

                <label for="phone">Phone</label>
                <input id="phone" name="phone" value="<?= cms_e((string)$form['phone']) ?>">

                <h2>Address</h2>
                <label for="address_line1">Street address</label>
                <input id="address_line1" name="address_line1" value="<?= cms_e((string)$form['address_line1']) ?>" required>

                <label for="address_line2">Apartment, suite, or additional address line</label>
                <input id="address_line2" name="address_line2" value="<?= cms_e((string)$form['address_line2']) ?>">

                <div class="settings-grid">
                    <div>
                        <label for="city">City</label>
                        <input id="city" name="city" value="<?= cms_e((string)$form['city']) ?>" required>
                    </div>
                    <div>
                        <label for="state">State</label>
                        <input id="state" name="state" value="<?= cms_e((string)$form['state']) ?>" required>
                    </div>
                </div>

                <label for="postal_code">Postal code</label>
                <input id="postal_code" name="postal_code" value="<?= cms_e((string)$form['postal_code']) ?>" required>

                <h2>Church Life</h2>
                <label for="current_church">Current or previous faith community</label>
                <input id="current_church" name="current_church" value="<?= cms_e((string)$form['current_church']) ?>">

                <label for="baptism_status">Baptism status</label>
                <select id="baptism_status" name="baptism_status">
                    <?php foreach (['' => 'Choose one', 'baptized' => 'Baptized', 'not_baptized' => 'Not baptized', 'unsure' => 'Not sure', 'prefer_conversation' => 'I would like to talk with someone'] as $value => $label): ?>
                        <option value="<?= cms_e($value) ?>" <?= $form['baptism_status'] === $value ? 'selected' : '' ?>><?= cms_e($label) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="sacraments_received">Sacraments, milestones, or pastoral history you would like us to know</label>
                <textarea id="sacraments_received" name="sacraments_received"><?= cms_e((string)$form['sacraments_received']) ?></textarea>

                <label for="ministries_interest">Ministries, service, formation, or community life interests</label>
                <textarea id="ministries_interest" name="ministries_interest"><?= cms_e((string)$form['ministries_interest']) ?></textarea>

                <label for="pastoral_notes">Pastoral notes, hopes, questions, or anything you want to share</label>
                <textarea id="pastoral_notes" name="pastoral_notes"><?= cms_e((string)$form['pastoral_notes']) ?></textarea>

                <label for="accessibility_needs">Access needs, communication preferences, or support that would help us welcome you well</label>
                <textarea id="accessibility_needs" name="accessibility_needs"><?= cms_e((string)$form['accessibility_needs']) ?></textarea>

                <h2>Additional Household Members</h2>
                <div class="family-members" data-family-members>
                    <?php foreach ($displayMembers as $index => $member): ?>
                        <fieldset class="family-member" data-family-member>
                            <legend>Household member <?= cms_e((string)($index + 1)) ?></legend>
                            <label for="member_name_<?= cms_e((string)$index) ?>">Name</label>
                            <input id="member_name_<?= cms_e((string)$index) ?>" name="members[<?= cms_e((string)$index) ?>][name]" value="<?= cms_e($member['name']) ?>">

                            <label for="member_preferred_name_<?= cms_e((string)$index) ?>">Name they would like us to use</label>
                            <input id="member_preferred_name_<?= cms_e((string)$index) ?>" name="members[<?= cms_e((string)$index) ?>][preferred_name]" value="<?= cms_e($member['preferred_name']) ?>">

                            <div class="settings-grid">
                                <div>
                                    <label for="member_pronouns_<?= cms_e((string)$index) ?>">Pronouns</label>
                                    <input id="member_pronouns_<?= cms_e((string)$index) ?>" name="members[<?= cms_e((string)$index) ?>][pronouns]" value="<?= cms_e($member['pronouns']) ?>">
                                </div>
                                <div>
                                    <label for="member_gender_identity_<?= cms_e((string)$index) ?>">Gender identity</label>
                                    <input id="member_gender_identity_<?= cms_e((string)$index) ?>" name="members[<?= cms_e((string)$index) ?>][gender_identity]" value="<?= cms_e($member['gender_identity']) ?>" placeholder="Optional">
                                </div>
                            </div>

                            <div class="settings-grid">
                                <div>
                                    <label for="member_date_of_birth_<?= cms_e((string)$index) ?>">Date of birth</label>
                                    <input id="member_date_of_birth_<?= cms_e((string)$index) ?>" name="members[<?= cms_e((string)$index) ?>][date_of_birth]" type="date" value="<?= cms_e($member['date_of_birth']) ?>">
                                </div>
                                <div>
                                    <label for="member_relationship_<?= cms_e((string)$index) ?>">Relationship to household or family</label>
                                    <input id="member_relationship_<?= cms_e((string)$index) ?>" name="members[<?= cms_e((string)$index) ?>][relationship]" value="<?= cms_e($member['relationship']) ?>" placeholder="Spouse, partner, child, parent, friend, your words">
                                </div>
                            </div>

                            <label for="member_notes_<?= cms_e((string)$index) ?>">Notes</label>
                            <textarea id="member_notes_<?= cms_e((string)$index) ?>" name="members[<?= cms_e((string)$index) ?>][notes]"><?= cms_e($member['notes']) ?></textarea>
                            <button type="button" class="button secondary remove-family-member" data-remove-family-member>Remove member</button>
                        </fieldset>
                    <?php endforeach; ?>
                </div>
                <p><button type="button" class="button secondary" data-add-family-member>Add household member</button></p>

                <label class="checkbox-label">
                    <input name="consent_to_contact" type="checkbox" value="1" <?= $form['consent_to_contact'] ? 'checked' : '' ?> required>
                    Holy Cross Parish and Friary may contact me about membership and pastoral care.
                </label>

                <?= HCaptcha::widget() ?>

                <p><button type="submit">Submit membership registration</button></p>
            </form>
        </article>
    </main>
    <footer class="site-footer">
        <div class="wrap">
            <a class="footer-admin-link" href="<?= cms_e(cms_base_url('/admin/')) ?>">Admin</a>
            <p>Copyright 2026 - Holy Cross Parish and Friary</p>
        </div>
    </footer>
    <script src="<?= cms_e(cms_base_url('/assets/js/membership.js')) ?>"></script>
</body>
</html>
