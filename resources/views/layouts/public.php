<?php

use Adheri\Support\Str;
use Adheri\Support\Url;
use Adheri\View;

/** @var string $content */
/** @var string $title */
/** @var array<string, string|null> $settings */
$settings = $settings ?? [];
$org = $settings['org_short_name'] ?? 'ADHERI';
$logo = Url::asset('img/ADHERI-new-logo-1.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= Str::e($title ?? $org) ?></title>
  <meta name="theme-color" content="#0071BC">
  <link rel="icon" href="<?= Str::e($logo) ?>" type="image/png">
  <?= View::render('partials/social-meta', [
      'settings' => $settings,
      'title' => $title ?? $org,
      'meta' => $meta ?? [],
  ]) ?>
  <link rel="preload" as="image" href="<?= Str::e(Url::asset('img/hero/01.jpg')) ?>">
  <link rel="stylesheet" href="<?= Str::e(Url::asset('css/public.css')) ?>">
</head>
<body>
  <div class="grain" aria-hidden="true"></div>

  <header class="site-header">
    <div class="nav-island">
      <a class="brand-lockup" href="<?= Str::e(Url::to('/')) ?>">
        <img src="<?= Str::e($logo) ?>" width="160" height="40" alt="<?= Str::e($org) ?>">
      </a>
      <ul class="nav-links">
        <li><a href="<?= Str::e(Url::to('/')) ?>">Home</a></li>
        <li><a href="<?= Str::e(Url::to('about')) ?>">About</a></li>
        <li><a href="<?= Str::e(Url::to('programs')) ?>">Programs</a></li>
        <li><a href="<?= Str::e(Url::to('gallery')) ?>">Gallery</a></li>
        <li><a href="<?= Str::e(Url::to('blog')) ?>">Blog</a></li>
        <li><a href="<?= Str::e(Url::to('contact')) ?>">Contact</a></li>
        <li><a href="<?= Str::e(Url::to('volunteer')) ?>">Volunteer</a></li>
      </ul>
      <div class="nav-cta">
        <a class="btn btn-primary" href="<?= Str::e(Url::to('donate')) ?>" style="font-size:0.85rem">Donate<span class="btn-icon" aria-hidden="true">↗</span></a>
        <button class="nav-toggle" type="button" data-nav-toggle aria-label="Open menu" aria-expanded="false" aria-controls="mobile-nav"><span></span></button>
      </div>
    </div>
  </header>

  <nav class="mobile-nav" id="mobile-nav" data-mobile-nav aria-hidden="true">
    <button class="mobile-nav-close" type="button" data-nav-close aria-label="Close menu">×</button>
    <div class="mobile-nav-links">
      <a href="<?= Str::e(Url::to('/')) ?>">Home</a>
      <a href="<?= Str::e(Url::to('about')) ?>">About</a>
      <a href="<?= Str::e(Url::to('programs')) ?>">Programs</a>
      <a href="<?= Str::e(Url::to('gallery')) ?>">Gallery</a>
      <a href="<?= Str::e(Url::to('blog')) ?>">Blog</a>
      <a href="<?= Str::e(Url::to('contact')) ?>">Contact</a>
      <a href="<?= Str::e(Url::to('volunteer')) ?>">Volunteer</a>
      <a href="<?= Str::e(Url::to('donate')) ?>">Donate</a>
    </div>
  </nav>

  <main>
    <?= $content ?>
  </main>

  <footer class="site-footer">
    <div class="footer-grid">
      <div>
        <h3><?= Str::e($org) ?></h3>
        <p><?= Str::e($settings['address'] ?? '') ?></p>
        <p><a href="mailto:<?= Str::e($settings['contact_email_primary'] ?? '') ?>"><?= Str::e($settings['contact_email_primary'] ?? '') ?></a></p>
        <p><?= Str::e($settings['phone_primary'] ?? '') ?></p>
        <h3 class="footer-social-heading">Follow us</h3>
        <?= View::render('partials/social-links', ['settings' => $settings, 'variant' => 'dark']) ?>
      </div>
      <div>
        <h3>Explore</h3>
        <ul>
          <li><a href="<?= Str::e(Url::to('about')) ?>">About</a></li>
          <li><a href="<?= Str::e(Url::to('programs')) ?>">Programs</a></li>
          <li><a href="<?= Str::e(Url::to('gallery')) ?>">Gallery</a></li>
          <li><a href="<?= Str::e(Url::to('blog')) ?>">Blog</a></li>
        </ul>
      </div>
      <div>
        <h3>Act</h3>
        <ul>
          <li><a href="<?= Str::e(Url::to('donate')) ?>">Donate</a></li>
          <li><a href="<?= Str::e(Url::to('volunteer')) ?>">Volunteer</a></li>
          <li><a href="<?= Str::e(Url::to('contact')) ?>">Contact</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-copy">
      <p>© <?= date('Y') ?> <?= Str::e($settings['org_name'] ?? 'ADHERI') ?>. All rights reserved.</p>
    </div>
  </footer>
  <script src="<?= Str::e(Url::asset('js/public.js')) ?>" defer></script>
</body>
</html>
