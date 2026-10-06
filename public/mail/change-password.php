<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = $mail->requireUser();
if ($mail->needsAccountSetup($user)) {
    header('Location: ' . $mail->url('account-setup'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $pass = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');
    if ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $mail->changePassword((int) $user['id'], $pass);
            Adheri\Mail\MailSession::flash('success', 'Password updated.');
            header('Location: ' . $mail->url('settings'));
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$title = 'Change password';
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
  <main class="mail-auth-card">
    <div class="mail-brand-lockup">
      <img class="mail-auth-logo" src="<?= mail_h(mail_asset('img/ADHERI-new-logo-1.png')) ?>" alt="ADHERI" width="180" height="46">
      <span class="mail-brand-word">Mail</span>
    </div>
    <h1>Change password</h1>
    <p class="mail-muted">Minimum 12 characters.</p>
    <?php if ($error !== ''): ?>
      <div class="mail-flash mail-flash-error"><?= mail_h($error) ?></div>
    <?php endif; ?>
    <form method="post" class="mail-auth-form">
      <?= csrf_field() ?>
      <label>New password <input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
      <label>Confirm <input type="password" name="password_confirm" required minlength="12" autocomplete="new-password"></label>
      <button type="submit" class="btn-mail-primary">Save password</button>
    </form>
    <p class="mail-auth-footer"><a href="<?= mail_h($mail->url('settings')) ?>">Back to settings</a></p>
  </main>
</body>
</html>
