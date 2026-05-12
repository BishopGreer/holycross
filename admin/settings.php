<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

cms_require_installation();
Auth::requireLogin();

$errors = [];
$saved = false;
$testSent = false;
$contactRecipient = Settings::get('contact_recipient_email');
$membershipRecipient = Settings::get('membership_recipient_email');
$hcaptchaEnabled = Settings::get('hcaptcha_enabled', '0');
$hcaptchaSiteKey = Settings::get('hcaptcha_site_key');
$hcaptchaSecretKey = Settings::get('hcaptcha_secret_key');
$mailTransport = Settings::get('mail_transport', 'mail');
$mailFromName = Settings::get('mail_from_name', 'Holy Cross Parish and Friary');
$mailFromEmail = Settings::get('mail_from_email');
$smtpHost = Settings::get('smtp_host');
$smtpPort = Settings::get('smtp_port', '587');
$smtpEncryption = Settings::get('smtp_encryption', 'tls');
$smtpUsername = Settings::get('smtp_username');
$smtpPassword = Settings::get('smtp_password');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $contactRecipient = trim((string)($_POST['contact_recipient_email'] ?? ''));
    $membershipRecipient = trim((string)($_POST['membership_recipient_email'] ?? ''));
    $hcaptchaEnabled = isset($_POST['hcaptcha_enabled']) ? '1' : '0';
    $hcaptchaSiteKey = trim((string)($_POST['hcaptcha_site_key'] ?? ''));
    $postedHcaptchaSecret = (string)($_POST['hcaptcha_secret_key'] ?? '');
    $hcaptchaSecretKey = $postedHcaptchaSecret === '' ? $hcaptchaSecretKey : trim($postedHcaptchaSecret);
    $mailTransport = (string)($_POST['mail_transport'] ?? 'mail');
    $mailFromName = trim((string)($_POST['mail_from_name'] ?? 'Holy Cross Parish and Friary'));
    $mailFromEmail = trim((string)($_POST['mail_from_email'] ?? ''));
    $smtpHost = trim((string)($_POST['smtp_host'] ?? ''));
    $smtpPort = trim((string)($_POST['smtp_port'] ?? '587'));
    $smtpEncryption = (string)($_POST['smtp_encryption'] ?? 'tls');
    $smtpUsername = trim((string)($_POST['smtp_username'] ?? ''));
    $postedPassword = (string)($_POST['smtp_password'] ?? '');
    $smtpPassword = $postedPassword === '' ? $smtpPassword : $postedPassword;

    if ($contactRecipient === '' || !filter_var($contactRecipient, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid contact recipient email address.';
    }
    if ($membershipRecipient !== '' && !filter_var($membershipRecipient, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid membership recipient email address.';
    }
    if ($hcaptchaEnabled === '1' && ($hcaptchaSiteKey === '' || $hcaptchaSecretKey === '')) {
        $errors[] = 'Enter both hCaptcha keys, or leave hCaptcha disabled.';
    }
    if (!in_array($mailTransport, ['mail', 'smtp'], true)) {
        $errors[] = 'Choose a valid mail transport.';
    }
    if ($mailFromEmail !== '' && !filter_var($mailFromEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid From email address.';
    }
    if ($mailTransport === 'smtp' && $smtpHost === '') {
        $errors[] = 'SMTP host is required when using SMTP.';
    }
    if ($mailTransport === 'smtp' && ((int)$smtpPort < 1 || (int)$smtpPort > 65535)) {
        $errors[] = 'SMTP port must be between 1 and 65535.';
    }
    if (!in_array($smtpEncryption, ['tls', 'ssl', 'none'], true)) {
        $errors[] = 'Choose a valid SMTP encryption option.';
    }

    if (!$errors) {
        Settings::set('contact_recipient_email', $contactRecipient);
        Settings::set('membership_recipient_email', $membershipRecipient);
        Settings::set('hcaptcha_enabled', $hcaptchaEnabled);
        Settings::set('hcaptcha_site_key', $hcaptchaSiteKey);
        Settings::set('hcaptcha_secret_key', $hcaptchaSecretKey);
        Settings::set('mail_transport', $mailTransport);
        Settings::set('mail_from_name', $mailFromName);
        Settings::set('mail_from_email', $mailFromEmail);
        Settings::set('smtp_host', $smtpHost);
        Settings::set('smtp_port', $smtpPort);
        Settings::set('smtp_encryption', $smtpEncryption);
        Settings::set('smtp_username', $smtpUsername);
        Settings::set('smtp_password', $smtpPassword);
        $saved = true;

        if (($_POST['action'] ?? '') === 'send_test') {
            $testHtml = '<!doctype html><html lang="en"><body style="margin:0;background:#f7f1e5;color:#25170c;font-family:Georgia,Times New Roman,serif;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f7f1e5;padding:24px 12px;"><tr><td align="center"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#fffdf8;border:1px solid #d9cbb5;border-top:8px solid #be202e;"><tr><td style="padding:28px;text-align:center;background:#603a17;color:#ffffff;font-family:American Typewriter,Courier New,serif;font-size:24px;font-weight:bold;">Holy Cross Parish and Friary</td></tr><tr><td style="padding:28px;"><h1 style="margin:0 0 14px;color:#be202e;font-family:American Typewriter,Courier New,serif;font-size:28px;">Mail Test</h1><p style="font-size:17px;line-height:1.6;">Your contact form mail settings are able to send this test message.</p></td></tr></table></td></tr></table></body></html>';
            [$testSent, $testError] = Mailer::send($contactRecipient, 'Holy Cross contact form mail test', $testHtml, $contactRecipient);
            if (!$testSent) {
                $errors[] = 'Settings saved, but the test email failed: ' . $testError;
            }
        }
    }
}

$title = 'Settings';
require __DIR__ . '/_header.php';
?>
<div class="toolbar">
    <h1>Settings</h1>
    <a class="button secondary" href="<?= cms_e(cms_base_url('/admin/')) ?>">Back to pages</a>
</div>
<?php if ($saved): ?>
    <p class="notice">Settings saved.</p>
<?php endif; ?>
<?php if ($testSent): ?>
    <p class="notice">Test email sent to <?= cms_e($contactRecipient) ?>.</p>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <p class="notice error"><?= cms_e($error) ?></p>
<?php endforeach; ?>
<form method="post" class="panel">
    <?= Csrf::field() ?>

    <h2>Contact Form</h2>
    <label for="contact_recipient_email">Send contact form messages to</label>
    <input id="contact_recipient_email" name="contact_recipient_email" type="email" value="<?= cms_e($contactRecipient) ?>" required>

    <h2>Membership Form</h2>
    <label for="membership_recipient_email">Send membership registrations to</label>
    <input id="membership_recipient_email" name="membership_recipient_email" type="email" value="<?= cms_e($membershipRecipient) ?>" placeholder="Leave blank to use contact recipient">

    <h2>hCaptcha</h2>
    <label class="checkbox-label">
        <input name="hcaptcha_enabled" type="checkbox" value="1" <?= $hcaptchaEnabled === '1' ? 'checked' : '' ?>>
        Enable hCaptcha on public forms
    </label>

    <label for="hcaptcha_site_key">hCaptcha site key</label>
    <input id="hcaptcha_site_key" name="hcaptcha_site_key" value="<?= cms_e($hcaptchaSiteKey) ?>">

    <label for="hcaptcha_secret_key">hCaptcha secret key</label>
    <input id="hcaptcha_secret_key" name="hcaptcha_secret_key" type="password" placeholder="<?= $hcaptchaSecretKey === '' ? '' : 'Leave blank to keep current secret key' ?>">

    <h2>Mail Delivery</h2>
    <label for="mail_transport">Mail transport</label>
    <select id="mail_transport" name="mail_transport">
        <option value="mail" <?= $mailTransport === 'mail' ? 'selected' : '' ?>>Native PHP mail</option>
        <option value="smtp" <?= $mailTransport === 'smtp' ? 'selected' : '' ?>>SMTP</option>
    </select>

    <label for="mail_from_name">From name</label>
    <input id="mail_from_name" name="mail_from_name" value="<?= cms_e($mailFromName) ?>">

    <label for="mail_from_email">From email</label>
    <input id="mail_from_email" name="mail_from_email" type="email" value="<?= cms_e($mailFromEmail) ?>" placeholder="no-reply@example.com">

    <div class="settings-grid">
        <div>
            <label for="smtp_host">SMTP host</label>
            <input id="smtp_host" name="smtp_host" value="<?= cms_e($smtpHost) ?>" placeholder="smtp.example.com">
        </div>
        <div>
            <label for="smtp_port">SMTP port</label>
            <input id="smtp_port" name="smtp_port" inputmode="numeric" value="<?= cms_e($smtpPort) ?>">
        </div>
    </div>

    <label for="smtp_encryption">SMTP encryption</label>
    <select id="smtp_encryption" name="smtp_encryption">
        <option value="tls" <?= $smtpEncryption === 'tls' ? 'selected' : '' ?>>STARTTLS</option>
        <option value="ssl" <?= $smtpEncryption === 'ssl' ? 'selected' : '' ?>>SSL/TLS</option>
        <option value="none" <?= $smtpEncryption === 'none' ? 'selected' : '' ?>>None</option>
    </select>

    <label for="smtp_username">SMTP username</label>
    <input id="smtp_username" name="smtp_username" value="<?= cms_e($smtpUsername) ?>">

    <label for="smtp_password">SMTP password</label>
    <input id="smtp_password" name="smtp_password" type="password" placeholder="<?= $smtpPassword === '' ? '' : 'Leave blank to keep current password' ?>">

    <p class="actions">
        <button type="submit" name="action" value="save">Save settings</button>
        <button type="submit" name="action" value="send_test">Save and send test email</button>
    </p>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
