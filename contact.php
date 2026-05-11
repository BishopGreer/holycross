<?php

declare(strict_types=1);

require_once __DIR__ . '/core/bootstrap.php';

cms_require_installation();
cms_start_session();

$config = cms_config();
$repo = new PageRepository();
$navPages = $repo->published();
$recipient = Settings::get('contact_recipient_email');
$errors = [];
$sent = false;
$form = [
    'name' => '',
    'email' => '',
    'subject' => '',
    'comments' => '',
];

function contact_email_template(string $heading, array $form, string $intro): string
{
    $siteName = (string)(cms_config()['app_name'] ?? 'Holy Cross Parish and Friary');
    $comments = nl2br(cms_e($form['comments']));

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
                                <tr>
                                    <td style="padding:10px;border-bottom:1px solid #d9cbb5;font-weight:bold;color:#603a17;">Subject</td>
                                    <td style="padding:10px;border-bottom:1px solid #d9cbb5;">' . cms_e($form['subject']) . '</td>
                                </tr>
                            </table>
                            <div style="padding:18px;border-left:5px solid #d8b35d;background:#fbf4e7;font-size:17px;line-height:1.65;">' . $comments . '</div>
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
        'subject' => trim((string)($_POST['subject'] ?? '')),
        'comments' => trim((string)($_POST['comments'] ?? '')),
    ];

    if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'The contact form recipient has not been configured yet.';
    }
    if ($form['name'] === '') {
        $errors[] = 'Contact name is required.';
    }
    if ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if ($form['subject'] === '') {
        $errors[] = 'Subject is required.';
    }
    if ($form['comments'] === '') {
        $errors[] = 'Comments are required.';
    }

    if (!$errors) {
        $siteName = (string)($config['app_name'] ?? 'Holy Cross Parish and Friary');
        $recipientSubject = $siteName . ': ' . $form['subject'];
        $copySubject = 'Copy of your message to ' . $siteName . ': ' . $form['subject'];
        $recipientHtml = contact_email_template('New Contact Message', $form, 'A new message was submitted through the parish website contact form.');
        $copyHtml = contact_email_template('Your Message Was Received', $form, 'Thank you for contacting us. A copy of your message is below for your records.');

        [$sentToRecipient, $recipientError] = Mailer::send($recipient, $recipientSubject, $recipientHtml, $form['email']);
        [$sentCopy, $copyError] = Mailer::send($form['email'], $copySubject, $copyHtml, $recipient);

        if ($sentToRecipient && $sentCopy) {
            $sent = true;
            $form = ['name' => '', 'email' => '', 'subject' => '', 'comments' => ''];
        } else {
            $errors[] = 'The message could not be sent. ' . ($recipientError ?: $copyError ?: 'Please check the mail settings.');
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact | <?= cms_e($config['app_name'] ?? 'Holy Cross Parish and Friary') ?></title>
    <meta name="description" content="Contact Holy Cross Parish and Friary.">
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
            </nav>
        </div>
    </header>
    <main class="page public-page">
        <article class="wrap content-panel">
            <h1>Contact</h1>
            <?php if ($sent): ?>
                <p class="notice">Thank you. Your message has been sent, and a copy has been emailed to you.</p>
            <?php endif; ?>
            <?php foreach ($errors as $error): ?>
                <p class="notice error"><?= cms_e($error) ?></p>
            <?php endforeach; ?>
            <form method="post" class="contact-form">
                <?= Csrf::field() ?>
                <label for="name">Contact name</label>
                <input id="name" name="name" value="<?= cms_e($form['name']) ?>" required>

                <label for="email">Email address</label>
                <input id="email" name="email" type="email" value="<?= cms_e($form['email']) ?>" required>

                <label for="subject">Subject</label>
                <input id="subject" name="subject" value="<?= cms_e($form['subject']) ?>" required>

                <label for="comments">Comments</label>
                <textarea id="comments" name="comments" required><?= cms_e($form['comments']) ?></textarea>

                <p><button type="submit">Send message</button></p>
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
