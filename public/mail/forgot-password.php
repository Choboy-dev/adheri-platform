<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Adheri\Mail\MailSession;
use Adheri\Modules\Auth\PasswordRecoveryService;

if ($mail->currentUser()) {
    header('Location: ' . $mail->url());
    exit;
}

$recovery = new PasswordRecoveryService();
$error = '';
$email = '';
$flash = MailSession::pullFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $channel = (string) ($_POST['channel'] ?? PasswordRecoveryService::CHANNEL_RECOVERY_EMAIL);
    $result = $recovery->start(PasswordRecoveryService::REALM_MAIL, $email, $channel);
    if (!$result['ok']) {
        $error = (string) ($result['error'] ?? 'Could not start password reset.');
    } else {
        MailSession::set('mail_recovery_challenge_id', (string) $result['challenge_id']);
        MailSession::set('mail_recovery_delivery_masked', (string) ($result['delivery_email_masked'] ?? ''));
        MailSession::flash('success', (string) ($result['message'] ?? 'Code sent.'));
        header('Location: ' . $mail->url('verify-otp'));
        exit;
    }
}

$title = $mail->appName() . ' — Forgot password';
mail_send_security_headers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= mail_h(Adheri\Mail\MailCsrf::token()) ?>">
  <title><?= mail_h($title) ?></title>
  <link rel="stylesheet" href="<?= mail_h(mail_asset('css/mail.css')) ?>?v=17">
</head>
<body class="mail-auth-body">
  <main class="mail-auth-card mail-auth-card--wide">
    <div class="mail-brand-lockup">
      <img class="mail-auth-logo" src="<?= mail_h(mail_asset('img/ADHERI-new-logo-1.png')) ?>" alt="ADHERI" width="180" height="46">
      <span class="mail-brand-word">Mail</span>
    </div>
    <h1>Forgot password</h1>
    <p class="mail-muted">Choose where we should send a one-time verification code.</p>
    <?php if ($flash && ($flash['type'] ?? '') === 'error'): ?>
      <div class="mail-flash mail-flash-error"><?= mail_h((string) $flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
      <div class="mail-flash mail-flash-error"><?= mail_h($error) ?></div>
    <?php endif; ?>
    <form method="post" class="mail-auth-form">
      <?= csrf_field() ?>
      <label>Mail account email
        <input type="email" name="email" required autocomplete="username" value="<?= mail_h($email) ?>">
      </label>
      <fieldset class="mail-recovery-channels">
        <legend>Send code to</legend>
        <label class="mail-recovery-option">
          <input type="radio" name="channel" value="recovery_email" checked>
          <span>
            <strong>My recovery email</strong>
            <small>The personal address saved on this account at first sign-in</small>
          </span>
        </label>
        <label class="mail-recovery-option">
          <input type="radio" name="channel" value="account_email">
          <span>
            <strong>This account email</strong>
            <small>Whatever mailbox address you entered above</small>
          </span>
        </label>
      </fieldset>
      <button type="submit" class="btn-mail-primary">Send verification code</button>
    </form>
    <p class="mail-auth-footer"><a href="<?= mail_h($mail->url('login')) ?>">Back to sign in</a></p>
  </main>
</body>
</html>
