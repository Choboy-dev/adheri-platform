<?php

use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var string $heading */
/** @var string $message */
/** @var string $status */
?>
<section class="section" style="padding-top:8rem">
  <div class="wrap" style="max-width:640px">
    <div class="form-panel">
      <div class="alert <?= $status === 'success' ? 'alert-success' : 'alert-error' ?>"><?= Str::e($heading) ?></div>
      <p><?= Str::e($message) ?></p>
      <div class="hero-actions" style="margin-top:1.25rem">
        <a class="btn btn-primary" href="<?= Str::e(Url::to('/')) ?>">Home<span class="btn-icon" aria-hidden="true">↗</span></a>
        <a class="btn btn-secondary" href="<?= Str::e(Url::to('contact')) ?>">Contact<span class="btn-icon" aria-hidden="true">↗</span></a>
      </div>
    </div>
  </div>
</section>
