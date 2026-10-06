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
    <img
      class="login-visual-img"
      src="<?= Str::e($panelImage) ?>"
      alt="ADHERI community programming"
      width="1200"
      height="1600"
    >
    <div class="login-visual-shade" aria-hidden="true"></div>
    <div class="login-visual-brand">
      <img src="<?= Str::e($logo) ?>" alt="ADHERI" width="160" height="40">
      <p>Advocates for Health &amp; Rights of Women and Youths Initiative</p>
    </div>
  </aside>

  <section class="login-panel">
    <div class="login-panel-inner">
      <div class="login-heading">
        <h1>Welcome back</h1>
        <p>Sign in to manage the ADHERI website.</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert" role="alert"><?= Str::e((string) $error) ?></div>
      <?php endif; ?>
      <?php if (!empty($success)): ?>
        <div class="alert alert-success" role="status"><?= Str::e((string) $success) ?></div>
      <?php endif; ?>

      <form class="login-form" method="post" action="<?= Str::e(Url::to('admin/login')) ?>">
        <?= Csrf::field() ?>

        <div class="field">
          <label for="email">Email</label>
          <input
            id="email"
            type="email"
            name="email"
            required
            autocomplete="username"
            placeholder="Email address"
            value="<?= Str::e((string) ($email ?? '')) ?>"
          >
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="password-field">
            <input
              id="password"
              type="password"
              name="password"
              required
              autocomplete="current-password"
              placeholder="Password"
            >
            <button
              class="password-toggle"
              type="button"
              data-password-toggle
              aria-label="Show password"
              aria-pressed="false"
            >
              <svg data-icon="show" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-8a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg>
              <svg data-icon="hide" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M2.1 3.51 3.51 2.1l18.39 18.39-1.41 1.41-3.28-3.28A12.3 12.3 0 0 1 12 19c-7.01 0-11.35-6.35-11.53-6.63L.3 12l.17-.37C.7 11.2 3.4 7.3 7.6 5.4L2.1 3.51zm5.7 3.28C9.05 6.28 10.48 6 12 6c7.01 0 11.35 6.35 11.53 6.63l.17.37-.17.37a15.4 15.4 0 0 1-3.36 3.86l-2.26-2.26A4.98 4.98 0 0 0 12 7c-.74 0-1.44.16-2.07.44L7.8 6.79zM12 9a3 3 0 0 1 3 3c0 .35-.06.69-.17 1L11 9.17c.31-.11.65-.17 1-.17zm-3 3c0-.35.06-.69.17-1L13 14.83A3 3 0 0 1 9 12z"/></svg>
            </button>
          </div>
        </div>

        <div class="login-row">
          <label class="remember">
            <input type="checkbox" name="remember" value="1">
            <span>Remember me</span>
          </label>
          <a class="login-forgot" href="<?= Str::e(Url::to('admin/forgot-password')) ?>">Forgot password?</a>
        </div>

        <button class="btn login-submit" type="submit">Sign in</button>
      </form>
    </div>
  </section>
</div>
<script>
(() => {
  const toggle = document.querySelector('[data-password-toggle]');
  const input = document.getElementById('password');
  if (!toggle || !input) return;
  const showIcon = toggle.querySelector('[data-icon="show"]');
  const hideIcon = toggle.querySelector('[data-icon="hide"]');
  const sync = () => {
    const visible = input.type === 'text';
    toggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
    toggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
    toggle.classList.toggle('is-visible', visible);
    if (showIcon) showIcon.style.display = visible ? 'none' : 'block';
    if (hideIcon) hideIcon.style.display = visible ? 'block' : 'none';
  };
  sync();
  toggle.addEventListener('click', () => {
    input.type = input.type === 'password' ? 'text' : 'password';
    sync();
  });
})();
</script>
