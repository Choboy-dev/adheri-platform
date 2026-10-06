<?php

use Adheri\Support\Str;
use Adheri\Support\Url;
use Adheri\Security\Csrf;

/** @var array<string, array<string, mixed>> $sections */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
$errors = $errors ?? [];
$old = is_array($old ?? null) ? $old : [];
$interestOptions = ['Advocacy', 'Counselling', 'Media', 'Artivism', 'Events', 'Admin/Operations', 'Other'];
$selected = $old['interests'] ?? [];
if (!is_array($selected)) {
    $selected = [];
}
?>
<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow">Volunteer</p>
    <h1>Volunteer with ADHERI</h1>
    <p class="lead"><?= Str::e($sections['intro']['body'] ?? '') ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap" style="max-width:720px">
    <div class="form-panel">
      <?php if (!empty($success)): ?><div class="alert alert-success"><?= Str::e((string) $success) ?></div><?php endif; ?>
      <form method="post" action="<?= Str::e(Url::to('volunteer')) ?>" class="form-grid">
        <?= Csrf::field() ?>
        <div>
          <label for="full_name">Full name</label>
          <input id="full_name" name="full_name" required value="<?= Str::e((string) ($old['full_name'] ?? '')) ?>">
          <?php if (isset($errors['full_name'])): ?><div class="field-error"><?= Str::e($errors['full_name']) ?></div><?php endif; ?>
        </div>
        <div class="form-grid two">
          <div>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" required value="<?= Str::e((string) ($old['email'] ?? '')) ?>">
            <?php if (isset($errors['email'])): ?><div class="field-error"><?= Str::e($errors['email']) ?></div><?php endif; ?>
          </div>
          <div>
            <label for="phone">Phone</label>
            <input id="phone" name="phone" value="<?= Str::e((string) ($old['phone'] ?? '')) ?>">
          </div>
        </div>
        <div>
          <label for="location">City / State</label>
          <input id="location" name="location" value="<?= Str::e((string) ($old['location'] ?? '')) ?>">
        </div>
        <div>
          <label>Areas of interest</label>
          <div class="amount-chips">
            <?php foreach ($interestOptions as $opt): ?>
              <label class="chip" style="cursor:pointer">
                <input type="checkbox" name="interests[]" value="<?= Str::e($opt) ?>" <?= in_array($opt, $selected, true) ? 'checked' : '' ?> style="width:auto;min-height:auto;margin-right:0.35rem">
                <?= Str::e($opt) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div>
          <label for="availability">Availability</label>
          <textarea id="availability" name="availability"><?= Str::e((string) ($old['availability'] ?? '')) ?></textarea>
        </div>
        <div>
          <label for="message">How do you want to help?</label>
          <textarea id="message" name="message" required><?= Str::e((string) ($old['message'] ?? '')) ?></textarea>
          <?php if (isset($errors['message'])): ?><div class="field-error"><?= Str::e($errors['message']) ?></div><?php endif; ?>
        </div>
        <button class="btn btn-primary" type="submit">Submit interest<span class="btn-icon" aria-hidden="true">↗</span></button>
      </form>
    </div>
  </div>
</section>
