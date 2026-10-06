<?php

use Adheri\Support\Str;
use Adheri\Support\Url;
use Adheri\Security\Csrf;

/** @var string $content */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= Str::e($title ?? 'Admin login') ?></title>
  <link rel="icon" href="<?= Str::e(Url::asset('img/ADHERI-new-logo-1.png')) ?>" type="image/png">
  <link rel="stylesheet" href="<?= Str::e(Url::asset('css/admin.css')) ?>">
</head>
<body>
  <?= $content ?>
</body>
</html>
