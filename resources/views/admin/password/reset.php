<?php

use Adheri\Support\Str;
use Adheri\Support\Url;
use Adheri\Security\Csrf;

$logo = Url::asset('img/ADHERI-new-logo-1.png');
$panelImage = Url::asset('img/presence/about.jpg');
?>
<div class="login-split">
  <aside class="login-visual" aria-hidden="false">
    <img class="login-visual-img" src="<?= Str::e($panelImage) ?>" alt="" width="1200" height="1600">
    <div class="login-visual-shade" aria-hidden="true"></div>
    <div class="login-visual-brand">
      <img src="<?= Str::e($logo) ?>" alt="ADHERI" width="160" height="40">
      <p>Choose a strong new password for your admin account.</p>
    </div>
  </aside>

  <section class="login-panel">
    <div class="login-panel-inner">
      <div class="login-heading">
        <h1>New password</h1>
        <p>Minimum 12 characters. Use a unique password you do not reuse elsewhere.</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert" role="alert"><?= Str::e((string) $error) ?></div>
      <?php endif; ?>

      <form class="login-form" method="post" action="<?= Str::e(Url::to('admin/reset-password')) ?>">
        <?= Csrf::field() ?>
        <div class="field">
          <label for="password">New password</label>
          <input id="password" type="password" name="password" required minlength="12" autocomplete="new-password">
        </div>
        <div class="field">
          <label for="password_confirm">Confirm password</label>
          <input id="password_confirm" type="password" name="password_confirm" required minlength="12" autocomplete="new-password">
        </div>
        <button class="btn login-submit" type="submit">Save password</button>
      </form>
    </div>
  </section>
</div>
