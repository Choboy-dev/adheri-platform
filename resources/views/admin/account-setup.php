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
      <p>Add a personal recovery email so password resets reach you — not a shared owner inbox.</p>
    </div>
  </aside>

  <section class="login-panel">
    <div class="login-panel-inner">
      <div class="login-heading">
        <h1>Finish setup</h1>
        <p>Signed in as <strong><?= Str::e((string) ($email ?? '')) ?></strong>. Save a personal email for password recovery.</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert" role="alert"><?= Str::e((string) $error) ?></div>
      <?php endif; ?>

      <form class="login-form" method="post" action="<?= Str::e(Url::to('admin/account-setup')) ?>">
        <?= Csrf::field() ?>
        <div class="field">
          <label for="recovery_email">Personal recovery email</label>
          <input id="recovery_email" type="email" name="recovery_email" required autocomplete="email"
                 placeholder="you@gmail.com"
                 value="<?= Str::e((string) ($recoveryEmail ?? '')) ?>">
          <p class="field-hint">Must be different from your admin login address. Reset codes can be sent here.</p>
        </div>
        <button class="btn login-submit" type="submit">Save and continue</button>
      </form>
    </div>
  </section>
</div>
