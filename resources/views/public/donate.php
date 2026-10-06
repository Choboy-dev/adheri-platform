<?php

use Adheri\Support\Str;
use Adheri\Support\Url;
use Adheri\Security\Csrf;

/** @var array<string, array<string, mixed>> $sections */
/** @var int $minAmount */
/** @var bool $gatewayConfigured */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
$errors = $errors ?? [];
$old = is_array($old ?? null) ? $old : [];
$presets = [1000, 5000, 10000, 25000];
?>
<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow">Donate</p>
    <h1>Support the work</h1>
    <p class="lead"><?= Str::e($sections['intro']['body'] ?? '') ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap" style="max-width:640px">
    <div class="form-panel">
      <?php if (!empty($error)): ?><div class="alert alert-error"><?= Str::e((string) $error) ?></div><?php endif; ?>
      <?php if (!$gatewayConfigured): ?>
        <div class="alert alert-error">Flutterwave keys are not configured yet. Add them in <code>.env</code> before taking live donations.</div>
      <?php endif; ?>
      <form method="post" action="<?= Str::e(Url::to('donate')) ?>" class="form-grid" data-donate-form data-min-amount="<?= (int) $minAmount ?>">
        <?= Csrf::field() ?>
        <div>
          <label for="donor_name">Full name</label>
          <input id="donor_name" name="donor_name" required value="<?= Str::e((string) ($old['donor_name'] ?? '')) ?>">
          <?php if (isset($errors['donor_name'])): ?><div class="field-error"><?= Str::e($errors['donor_name']) ?></div><?php endif; ?>
        </div>
        <div class="form-grid two">
          <div>
            <label for="donor_email">Email</label>
            <input id="donor_email" type="email" name="donor_email" required value="<?= Str::e((string) ($old['donor_email'] ?? '')) ?>">
            <?php if (isset($errors['donor_email'])): ?><div class="field-error"><?= Str::e($errors['donor_email']) ?></div><?php endif; ?>
          </div>
          <div>
            <label for="donor_phone">Phone (optional)</label>
            <input id="donor_phone" name="donor_phone" value="<?= Str::e((string) ($old['donor_phone'] ?? '')) ?>">
          </div>
        </div>
        <div>
          <label for="amount">Amount (NGN)</label>
          <div class="amount-chips">
            <?php foreach ($presets as $preset): ?>
              <button type="button" class="chip" data-amount-chip="<?= (int) $preset ?>">₦<?= number_format($preset) ?></button>
            <?php endforeach; ?>
          </div>
          <input
            id="amount"
            name="amount"
            type="number"
            step="1"
            inputmode="numeric"
            required
            value="<?= Str::e((string) ($old['amount'] ?? $minAmount)) ?>"
            aria-describedby="amount-error"
            aria-invalid="<?= isset($errors['amount']) ? 'true' : 'false' ?>"
            class="<?= isset($errors['amount']) ? 'is-invalid' : '' ?>"
          >
          <div id="amount-error" class="field-error" role="alert"<?= isset($errors['amount']) ? '' : ' hidden' ?>><?= isset($errors['amount']) ? Str::e($errors['amount']) : '' ?></div>
        </div>
        <button class="btn btn-primary" type="submit" <?= $gatewayConfigured ? '' : 'disabled' ?>>Continue to payment<span class="btn-icon" aria-hidden="true">↗</span></button>
      </form>
    </div>
  </div>
</section>
