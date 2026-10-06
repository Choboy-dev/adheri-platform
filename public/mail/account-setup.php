<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = $mail->currentUser();
if ($user === null) {
    header('Location: ' . $mail->url('login'));
    exit;
}

if (!$mail->needsAccountSetup($user)) {
    header('Location: ' . $mail->url());
    exit;
}

$error = '';
$needsPassword = (int) ($user['must_change_password'] ?? 0) === 1;
$recoveryPrefill = (string) ($user['recovery_email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $result = $mail->completeAccountSetup(
        $user,
        (string) ($_POST['recovery_email'] ?? ''),
        isset($_POST['password']) ? (string) $_POST['password'] : null,
        isset($_POST['password_confirm']) ? (string) $_POST['password_confirm'] : null
    );
    if (!$result['ok']) {
        $error = (string) ($result['error'] ?? 'Could not save account setup.');
        $recoveryPrefill = (string) ($_POST['recovery_email'] ?? $recoveryPrefill);
    } else {
        Adheri\Mail\MailSession::flash('success', 'Account setup complete. Welcome to ADHERI Mail.');
        header('Location: ' . $mail->url());
        exit;
    }
}

$title = 'Finish account setup';
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
    <h1>Finish setup</h1>
    <p class="mail-muted">
      Add a personal recovery email
      <?php if ($needsPassword): ?>and choose your own password<?php endif; ?>
      so you can reset access later without relying on someone else’s inbox.
    </p>
    <?php if ($error !== ''): ?>
      <div class="mail-flash mail-flash-error"><?= mail_h($error) ?></div>
    <?php endif; ?>
    <form method="post" class="mail-auth-form">
      <?= csrf_field() ?>
      <?php if ($needsPassword): ?>
        <label>New password
          <input type="password" name="password" required minlength="12" autocomplete="new-password">
        </label>
        <label>Confirm password
          <input type="password" name="password_confirm" required minlength="12" autocomplete="new-password">
        </label>
      <?php endif; ?>
      <label>Personal recovery email
        <input type="email" name="recovery_email" required autocomplete="email"
               placeholder="you@gmail.com"
               value="<?= mail_h($recoveryPrefill) ?>">
      </label>
      <p class="mail-field-hint">Use an email you control outside this mailbox (for example Gmail or Outlook). Password reset codes can be sent there.</p>
      <button type="submit" class="btn-mail-primary">Save and continue</button>
    </form>
  </main>
</body>
</html>
