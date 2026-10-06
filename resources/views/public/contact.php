<?php

use Adheri\Support\Str;
use Adheri\Support\Url;
use Adheri\Security\Csrf;
use Adheri\View;

/** @var array<string, string|null> $settings */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
$errors = $errors ?? [];
$old = is_array($old ?? null) ? $old : [];
?>
<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow">Contact</p>
    <h1>Do you have any questions? We’re here for you.</h1>
  </div>
</section>

<section class="section">
  <div class="wrap split">
    <div>
      <h2>Contact information</h2>
      <p><a href="mailto:<?= Str::e($settings['contact_email_primary'] ?? '') ?>"><?= Str::e($settings['contact_email_primary'] ?? '') ?></a></p>
      <p><a href="mailto:<?= Str::e($settings['contact_email_info'] ?? '') ?>"><?= Str::e($settings['contact_email_info'] ?? '') ?></a></p>
      <p><?= Str::e($settings['phone_primary'] ?? '') ?></p>
      <p><?= Str::e($settings['phone_secondary'] ?? '') ?></p>
      <h3 style="margin-top:1.5rem">Visit us</h3>
      <p><?= Str::e($settings['org_name'] ?? '') ?><br><?= Str::e($settings['address'] ?? '') ?></p>
      <?php if (!empty($settings['map_url'])): ?>
        <p><a href="<?= Str::e($settings['map_url']) ?>" target="_blank" rel="noopener">Open in Google Maps</a></p>
      <?php endif; ?>
      <h3 class="contact-social-heading">Follow us</h3>
      <?= View::render('partials/social-links', ['settings' => $settings, 'variant' => 'light']) ?>
    </div>
    <div class="form-panel">
      <h2>Stay connected</h2>
      <p class="lead">Fill the form below. We’ll get back to you soon.</p>
      <?php if (!empty($success)): ?><div class="alert alert-success"><?= Str::e((string) $success) ?></div><?php endif; ?>
      <form method="post" action="<?= Str::e(Url::to('contact')) ?>" class="form-grid">
        <?= Csrf::field() ?>
        <div class="form-grid two">
          <div>
            <label for="first_name">First name</label>
            <input id="first_name" name="first_name" required value="<?= Str::e((string) ($old['first_name'] ?? '')) ?>">
            <?php if (isset($errors['first_name'])): ?><div class="field-error"><?= Str::e($errors['first_name']) ?></div><?php endif; ?>
          </div>
          <div>
            <label for="last_name">Last name</label>
            <input id="last_name" name="last_name" required value="<?= Str::e((string) ($old['last_name'] ?? '')) ?>">
            <?php if (isset($errors['last_name'])): ?><div class="field-error"><?= Str::e($errors['last_name']) ?></div><?php endif; ?>
          </div>
        </div>
        <div>
          <label for="email">Email</label>
          <input id="email" type="email" name="email" required value="<?= Str::e((string) ($old['email'] ?? '')) ?>">
          <?php if (isset($errors['email'])): ?><div class="field-error"><?= Str::e($errors['email']) ?></div><?php endif; ?>
        </div>
        <div>
          <label for="phone">Phone</label>
          <input id="phone" name="phone" value="<?= Str::e((string) ($old['phone'] ?? '')) ?>">
        </div>
        <div>
          <label for="message">Message</label>
          <textarea id="message" name="message" required><?= Str::e((string) ($old['message'] ?? '')) ?></textarea>
          <?php if (isset($errors['message'])): ?><div class="field-error"><?= Str::e($errors['message']) ?></div><?php endif; ?>
        </div>
        <button class="btn btn-primary" type="submit">Send message<span class="btn-icon" aria-hidden="true">↗</span></button>
      </form>
    </div>
  </div>
</section>
