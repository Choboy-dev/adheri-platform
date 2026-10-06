<?php

use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var list<array<string, mixed>> $albums */
?>
<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow">Gallery</p>
    <h1>Albums from the work</h1>
    <p class="lead">A museum-paced archive of ADHERI programs, artivism, and community moments.</p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php if ($albums === []): ?>
      <p class="lead">Albums will appear here once media is uploaded in admin.</p>
    <?php else: ?>
      <div class="album-bento">
        <?php foreach ($albums as $album): ?>
          <a class="album-tile" href="<?= Str::e(Url::to('gallery/' . $album['slug'])) ?>">
            <?php if (!empty($album['cover_url'])): ?>
              <img src="<?= Str::e(Url::to(ltrim((string) $album['cover_url'], '/'))) ?>" alt="" loading="lazy">
            <?php endif; ?>
            <div class="album-tile-body">
              <h3><?= Str::e((string) $album['title']) ?></h3>
              <p><?= (int) ($album['media_count'] ?? 0) ?> pieces<?= !empty($album['event_date']) ? ' · ' . Str::e((string) $album['event_date']) : '' ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
