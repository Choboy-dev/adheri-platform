<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Adheri\Mail\MailSession;
use Adheri\Modules\Auth\PasswordRecoveryService;

if ($mail->currentUser()) {
    header('Location: ' . $mail->url());
    exit;
}

$token = (string) MailSession::get('mail_recovery_reset_token', '');
if ($token === '') {
    MailSession::flash('error', 'Verify your code before setting a new password.');
    header('Location: ' . $mail->url('forgot-password'));
    exit;
}

$recovery = new PasswordRecoveryService();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $result = $recovery->resetPassword(
        $token,
        (string) ($_POST['password'] ?? ''),
        (string) ($_POST['password_confirm'] ?? '')
    );
    if (!$result['ok']) {
        $error = (string) ($result['error'] ?? 'Could not update password.');
    } else {
        MailSession::forget('mail_recovery_reset_token');
        MailSession::forget('mail_recovery_delivery_masked');
        MailSession::forget('mail_recovery_challenge_id');
        MailSession::flash('success', 'Password updated. Sign in with your new password.');
        header('Location: ' . $mail->url('login'));
        exit;
    }
}

$title = $mail->appName() . ' — New password';
mail_send_security_headers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= mail_h(Adheri\Mail\MailCsrf::token()) ?>">
  <title><?= mail_h($title) ?></title>
  <link rel="stylesheet" href="<?= mail_h(mail_asset('css/mail.css')) ?>?v=16">
</head>
<body class="mail-auth-body">
  <main class="mail-auth-card">
    <div class="mail-brand-lockup">
      <img class="mail-auth-logo" src="<?= mail_h(mail_asset('img/ADHERI-new-logo-1.png')) ?>" alt="ADHERI" width="180" height="46">
      <span class="mail-brand-word">Mail</span>
    </div>
    <h1>New password</h1>
    <p class="mail-muted">Minimum 12 characters. Use a unique password you do not reuse elsewhere.</p>
    <?php if ($error !== ''): ?>
      <div class="mail-flash mail-flash-error"><?= mail_h($error) ?></div>
    <?php endif; ?>
    <form method="post" class="mail-auth-form">
      <?= csrf_field() ?>
      <label>New password
        <input type="password" name="password" required minlength="12" autocomplete="new-password">
      </label>
      <label>Confirm password
        <input type="password" name="password_confirm" required minlength="12" autocomplete="new-password">
      </label>
      <button type="submit" class="btn-mail-primary">Save password</button>
    </form>
  </main>
</body>
</html>
