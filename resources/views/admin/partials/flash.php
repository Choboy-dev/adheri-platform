<?php

use Adheri\Support\Flash;
use Adheri\Support\Str;

$success = Flash::get('success');
$error = Flash::get('error');
?>
<?php if (is_string($success) && $success !== ''): ?>
  <div class="alert alert-success" role="alert"><?= Str::e($success) ?></div>
<?php endif; ?>
<?php if (is_string($error) && $error !== ''): ?>
  <div class="alert" role="alert"><?= Str::e($error) ?></div>
<?php endif; ?>
