<?php

declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';

cms_require_installation();
cms_start_session();

$config = cms_config();
$repo = new PageRepository();
$navPages = $repo->published();
$errors = [];
$sent = false;
$form = [
    'name' => '',
    'email' => '',
    'request' => '',
];

function prayer_admin_recipients(): array
{
    $emails = Database::connect()
        ->query('SELECT email FROM users WHERE role = "admin" AND email <> "" ORDER BY username ASC')
        ->fetchAll(PDO::FETCH_COLUMN);
    $valid = [];

    foreach ($emails as $email) {
        $email = trim((string)$email);
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $valid[strtolower($email)] = $email;
        }
    }

    return array_values($valid);
}

function prayer_email_template(string $heading, array $form, string $intro): string
{
    $siteName = (string)(cms_config()['app_name'] ?? 'Holy Cross Parish and Friary');
    $request = nl2br(cms_e($form['request']));

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
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#fffdf8;border:1px solid #d9cbb5;border-top:8px solid #be202e;">
                    <tr>
                        <td style="padding:28px 28px 10px;text-align:center;background:#603a17;color:#ffffff;">
                            <div style="font-family:American Typewriter,Courier New,serif;font-size:24px;font-weight:bold;">' . cms_e($siteName) . '</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <h1 style="margin:0 0 14px;color:#be202e;font-family:American Typewriter,Courier New,serif;font-size:28px;line-height:1.2;">' . cms_e($heading) . '</h1>
                            <p style="margin:0 0 20px;font-size:17px;line-height:1.6;">' . cms_e($intro) . '</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin:0 0 20px;">
                                <tr>
                                    <td style="padding:10px;border-bottom:1px solid #d9cbb5;font-weight:bold;color:#603a17;width:34%;">Name</td>
                                    <td style="padding:10px;border-bottom:1px solid #d9cbb5;">' . cms_e($form['name']) . '</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px;border-bottom:1px solid #d9cbb5;font-weight:bold;color:#603a17;">Email</td>
                                    <td style="padding:10px;border-bottom:1px solid #d9cbb5;">' . cms_e($form['email']) . '</td>
                                </tr>
                            </table>
                            <div style="padding:18px;border-left:5px solid #d8b35d;background:#fbf4e7;font-size:17px;line-height:1.65;">' . $request . '</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $form = [
        'name' => trim((string)($_POST['name'] ?? '')),
        'email' => trim((string)($_POST['email'] ?? '')),
        'request' => trim((string)($_POST['request'] ?? '')),
    ];
    $adminRecipients = prayer_admin_recipients();

    if (!$adminRecipients) {
        $errors[] = 'No admin email addresses are available for prayer request notifications. Add or update admin emails on the Users page.';
    }
    if ($form['name'] === '') {
        $errors[] = 'Name is required.';
    }
    if ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if ($form['request'] === '') {
        $errors[] = 'Prayer request is required.';
    }
    [$captchaOk, $captchaError] = HCaptcha::verify(
        (string)($_POST['h-captcha-response'] ?? ''),
        (string)($_SERVER['REMOTE_ADDR'] ?? '')
    );
    if (!$captchaOk) {
        $errors[] = $captchaError;
    }

    if (!$errors) {
        $siteName = (string)($config['app_name'] ?? 'Holy Cross Parish and Friary');
        $adminHtml = prayer_email_template('New Prayer Request', $form, 'A new prayer request was submitted through the parish website.');
        $copyHtml = prayer_email_template('Your Prayer Request Was Received', $form, 'Thank you for sharing your prayer request with Holy Cross Parish and Friary. A copy is below for your records.');
        $adminSubject = $siteName . ': Prayer request from ' . $form['name'];
        $adminErrors = [];

        foreach ($adminRecipients as $adminRecipient) {
            [$sentToAdmin, $adminError] = Mailer::send($adminRecipient, $adminSubject, $adminHtml, $form['email']);
            if (!$sentToAdmin) {
                $adminErrors[] = $adminRecipient . ': ' . $adminError;
            }
        }

        [$sentCopy, $copyError] = Mailer::send($form['email'], 'Copy of your prayer request to ' . $siteName, $copyHtml, $adminRecipients[0] ?? '');

        if (!$adminErrors && $sentCopy) {
            $sent = true;
            $form = ['name' => '', 'email' => '', 'request' => ''];
        } else {
            $emailError = $adminErrors ? implode(' ', $adminErrors) : ($copyError ?: 'Please check the mail settings.');
            $errors[] = 'The prayer request could not be emailed. ' . $emailError;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prayer Request | <?= cms_e(cms_site_title()) ?></title>
    <meta name="description" content="Submit a prayer request to Holy Cross Parish and Friary.">
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
            <h1>Prayer Request</h1>
            <?php if ($sent): ?>
                <p class="notice">Thank you. Your prayer request has been sent, and a copy has been emailed to you.</p>
            <?php endif; ?>
            <?php foreach ($errors as $error): ?>
                <p class="notice error"><?= cms_e($error) ?></p>
            <?php endforeach; ?>
            <form method="post" class="contact-form">
                <?= Csrf::field() ?>
                <label for="name">Name</label>
                <input id="name" name="name" value="<?= cms_e($form['name']) ?>" required>

                <label for="email">Email address</label>
                <input id="email" name="email" type="email" value="<?= cms_e($form['email']) ?>" required>

                <label for="request">Prayer request</label>
                <textarea id="request" name="request" required><?= cms_e($form['request']) ?></textarea>

                <?= HCaptcha::widget() ?>

                <p><button type="submit">Send prayer request</button></p>
            </form>
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
