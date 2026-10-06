<?php

use Adheri\Support\Str;
use Adheri\Support\Url;
use Adheri\Security\Csrf;

$logo = Url::asset('img/ADHERI-new-logo-1.png');
$panelImage = Url::asset('img/presence/about.jpg');
?>
<div class="login-split">
  <aside class="login-visual" aria-hidden="false">
    <a class="login-back" href="<?= Str::e(Url::to('/')) ?>" aria-label="Back to website">
      <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
    </a>
    <img class="login-visual-img" src="<?= Str::e($panelImage) ?>" alt="" width="1200" height="1600">
    <div class="login-visual-shade" aria-hidden="true"></div>
    <div class="login-visual-brand">
      <img src="<?= Str::e($logo) ?>" alt="ADHERI" width="160" height="40">
      <p>Reset your Website Admin password securely.</p>
    </div>
  </aside>

  <section class="login-panel">
    <div class="login-panel-inner">
      <div class="login-heading">
        <h1>Forgot password</h1>
        <p>Choose where we should send a one-time verification code.</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert" role="alert"><?= Str::e((string) $error) ?></div>
      <?php endif; ?>
      <?php if (!empty($success)): ?>
        <div class="alert alert-success" role="status"><?= Str::e((string) $success) ?></div>
      <?php endif; ?>

      <form class="login-form" method="post" action="<?= Str::e(Url::to('admin/forgot-password')) ?>">
        <?= Csrf::field() ?>

        <div class="field">
          <label for="email">Admin account email</label>
          <input id="email" type="email" name="email" required autocomplete="username"
                 value="<?= Str::e((string) ($email ?? '')) ?>" placeholder="admin@…">
        </div>

        <fieldset class="recovery-channels">
          <legend>Send code to</legend>
          <label class="recovery-option">
            <input type="radio" name="channel" value="recovery_email" checked>
            <span>
              <strong>My recovery email</strong>
              <small>The personal address saved on this account at first sign-in</small>
            </span>
          </label>
          <label class="recovery-option">
            <input type="radio" name="channel" value="account_email">
            <span>
              <strong>This account email</strong>
              <small>Whatever address you entered above</small>
            </span>
          </label>
        </fieldset>

        <button class="btn login-submit" type="submit">Send verification code</button>
      </form>

      <p class="login-footer-link">
        <a href="<?= Str::e(Url::to('admin/login')) ?>">Back to sign in</a>
      </p>
    </div>
  </section>
</div>
