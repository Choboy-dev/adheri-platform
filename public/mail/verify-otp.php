<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Adheri\Mail\MailSession;
use Adheri\Modules\Auth\PasswordRecoveryService;

if ($mail->currentUser()) {
    header('Location: ' . $mail->url());
    exit;
}

$challengeId = (string) MailSession::get('mail_recovery_challenge_id', '');
if ($challengeId === '') {
    MailSession::flash('error', 'Start password recovery first.');
    header('Location: ' . $mail->url('forgot-password'));
    exit;
}

$recovery = new PasswordRecoveryService();
$error = '';
$flash = MailSession::pullFlash();
$deliveryMasked = (string) MailSession::get('mail_recovery_delivery_masked', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $otp = (string) ($_POST['otp'] ?? '');
    $result = $recovery->verifyOtp($challengeId, $otp);
    if (!$result['ok']) {
        $error = (string) ($result['error'] ?? 'Invalid code.');
    } else {
        MailSession::set('mail_recovery_reset_token', (string) $result['reset_token']);
        MailSession::forget('mail_recovery_challenge_id');
        header('Location: ' . $mail->url('reset-password'));
        exit;
    }
}

$title = $mail->appName() . ' — Enter code';
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
    <h1>Enter code</h1>
    <p class="mail-muted">
      We sent a 6-digit code
      <?php if ($deliveryMasked !== ''): ?>
        to <strong><?= mail_h($deliveryMasked) ?></strong>
      <?php else: ?>
        to your chosen inbox
      <?php endif; ?>.
    </p>
    <?php if ($flash && ($flash['type'] ?? '') === 'success'): ?>
      <div class="mail-flash mail-flash-success"><?= mail_h((string) $flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
      <div class="mail-flash mail-flash-error"><?= mail_h($error) ?></div>
    <?php endif; ?>
    <form method="post" class="mail-auth-form">
      <?= csrf_field() ?>
      <label>Verification code
        <input type="text" name="otp" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="6-digit code">
      </label>
      <button type="submit" class="btn-mail-primary">Verify code</button>
    </form>
    <p class="mail-auth-footer"><a href="<?= mail_h($mail->url('forgot-password')) ?>">Request a new code</a></p>
  </main>
</body>
</html>
