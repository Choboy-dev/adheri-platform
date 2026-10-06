<?php

use Adheri\Support\Str;
use Adheri\Support\Url;
use Adheri\Security\Csrf;

$logo = Url::asset('img/ADHERI-new-logo-1.png');
$panelImage = Url::asset('img/presence/about.jpg');
?>
<div class="login-split">
  <aside class="login-visual" aria-hidden="false">
    <a class="login-back" href="<?= Str::e(Url::to('admin/forgot-password')) ?>" aria-label="Back">
      <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
    </a>
    <img class="login-visual-img" src="<?= Str::e($panelImage) ?>" alt="" width="1200" height="1600">
    <div class="login-visual-shade" aria-hidden="true"></div>
    <div class="login-visual-brand">
      <img src="<?= Str::e($logo) ?>" alt="ADHERI" width="160" height="40">
      <p>Enter the code from your email to continue.</p>
    </div>
  </aside>

  <section class="login-panel">
    <div class="login-panel-inner">
      <div class="login-heading">
        <h1>Enter code</h1>
        <p>
          We sent a 6-digit code
          <?php if (!empty($deliveryMasked)): ?>
            to <strong><?= Str::e((string) $deliveryMasked) ?></strong>
          <?php else: ?>
            to your chosen inbox
          <?php endif; ?>.
        </p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert" role="alert"><?= Str::e((string) $error) ?></div>
      <?php endif; ?>
      <?php if (!empty($success)): ?>
        <div class="alert alert-success" role="status"><?= Str::e((string) $success) ?></div>
      <?php endif; ?>

      <form class="login-form" method="post" action="<?= Str::e(Url::to('admin/verify-otp')) ?>">
        <?= Csrf::field() ?>
        <div class="field">
          <label for="otp">Verification code</label>
          <input id="otp" type="text" name="otp" required inputmode="numeric" pattern="[0-9]{6}"
                 maxlength="6" autocomplete="one-time-code" placeholder="6-digit code">
        </div>
        <button class="btn login-submit" type="submit">Verify code</button>
      </form>

      <p class="login-footer-link">
        <a href="<?= Str::e(Url::to('admin/forgot-password')) ?>">Request a new code</a>
      </p>
    </div>
  </section>
</div>
