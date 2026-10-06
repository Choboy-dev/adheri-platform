<?php

use Adheri\Support\Flash;
use Adheri\Support\Str;
use Adheri\Support\Url;
use Adheri\Security\Csrf;

$flashSuccess = Flash::get('success');
$flashError = Flash::get('error');

/** @var string $content */
/** @var string $title */
/** @var array<string, mixed>|null $authUser */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$nav = [
  'admin' => 'Dashboard',
  'admin/pages' => 'Pages',
  'admin/programs' => 'Programs',
  'admin/announcements' => 'Announcements',
  'admin/testimonials' => 'Testimonials',
  'admin/people' => 'People',
  'admin/media' => 'Media',
  'admin/albums' => 'Albums',
  'admin/posts' => 'Blog',
  'admin/inquiries' => 'Inquiries',
  'admin/donations' => 'Donations',
  'admin/settings' => 'Settings',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= Str::e($title ?? 'Admin') ?> — ADHERI</title>
  <link rel="stylesheet" href="<?= Str::e(Url::asset('css/admin.css')) ?>">
</head>
<body>
  <div class="admin-shell">
    <aside class="admin-side">
      <a class="logo" href="<?= Str::e(Url::to('admin')) ?>">ADHERI Admin</a>
      <nav>
        <?php foreach ($nav as $href => $label): ?>
          <?php $active = str_contains($path, '/' . $href) || ($href === 'admin' && preg_match('#/admin/?$#', $path)); ?>
          <a class="<?= $active ? 'is-active' : '' ?>" href="<?= Str::e(Url::to($href)) ?>"><?= Str::e($label) ?></a>
        <?php endforeach; ?>
      </nav>
    </aside>
    <div class="admin-main">
      <div class="admin-top">
        <h1><?= Str::e($title ?? 'Admin') ?></h1>
        <div>
          <span class="muted"><?= Str::e($authUser['name'] ?? '') ?></span>
          <form method="post" action="<?= Str::e(Url::to('admin/logout')) ?>" style="display:inline">
            <?= Csrf::field() ?>
            <button class="btn btn-ghost" type="submit">Log out</button>
          </form>
        </div>
      </div>
      <?php if ($flashSuccess): ?><div class="alert alert-success"><?= Str::e((string) $flashSuccess) ?></div><?php endif; ?>
      <?php if ($flashError): ?><div class="alert"><?= Str::e((string) $flashError) ?></div><?php endif; ?>
      <?= $content ?>
    </div>
  </div>
</body>
</html>
