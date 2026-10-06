<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if ($mail->currentUser()) {
    header('Location: ' . $mail->url());
    exit;
}

$error = '';
$success = '';
$flash = Adheri\Mail\MailSession::pullFlash();
if ($flash) {
    if (($flash['type'] ?? '') === 'success') {
        $success = (string) $flash['message'];
    } elseif (($flash['type'] ?? '') === 'error') {
        $error = (string) $flash['message'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $user = $mail->authenticate($email, $password);
    if ($user) {
        $mail->login($user);
        header('Location: ' . ($mail->needsAccountSetup($user) ? $mail->url('account-setup') : $mail->url()));
        exit;
    }
    $error = Adheri\Mail\MailSession::tooManyLoginAttempts()
        ? 'Too many attempts. Try again in 15 minutes.'
        : 'Invalid email or password.';
}

$title = $mail->appName() . ' — Sign in';
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
    <?php if ($error !== ''): ?>
      <div class="mail-flash mail-flash-error"><?= mail_h($error) ?></div>
    <?php endif; ?>
    <?php if ($success !== ''): ?>
      <div class="mail-flash mail-flash-success"><?= mail_h($success) ?></div>
    <?php endif; ?>
    <form method="post" class="mail-auth-form">
      <?= csrf_field() ?>
      <label>Email <input type="email" name="email" required autocomplete="username" value="<?= mail_h($_POST['email'] ?? '') ?>"></label>
      <label>Password <input type="password" name="password" required autocomplete="current-password"></label>
      <div class="mail-auth-row">
        <a class="mail-auth-forgot" href="<?= mail_h($mail->url('forgot-password')) ?>">Forgot password?</a>
      </div>
      <button type="submit" class="btn-mail-primary">Sign in</button>
    </form>
  </main>
</body>
</html>
