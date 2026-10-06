<?php

use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var list<array<string, mixed>> $items */
?>
<div class="media-studio-head" style="margin-bottom:1.15rem">
  <p class="media-studio-lead" style="margin:0">Albums are curated photo sets for the public Gallery.</p>
  <a class="btn" href="<?= Str::e(Url::to('admin/albums/new')) ?>">New album<span class="btn-icon" aria-hidden="true" style="margin-left:0.45rem;width:1.75rem;height:1.75rem;border-radius:999px;display:inline-grid;place-items:center;background:rgba(255,255,255,0.2)">↗</span></a>
</div>

<div class="panel">
  <?php if ($items === []): ?>
    <div class="media-empty" style="border-radius:1rem">
      <div class="media-empty-visual" aria-hidden="true"><span></span><span></span><span></span></div>
      <h3>No albums yet</h3>
      <p>Create an album, then attach photos from the media library for the Gallery page.</p>
    </div>
  <?php else: ?>
    <div class="album-index-grid">
      <?php foreach ($items as $row): ?>
        <a class="album-index-card" href="<?= Str::e(Url::to('admin/albums/' . (int) $row['id'] . '/edit')) ?>">
          <div class="album-index-cover">
            <?php if (!empty($row['cover_url'])): ?>
              <img src="<?= Str::e(Url::to(ltrim((string) $row['cover_url'], '/'))) ?>" alt="" loading="lazy">
            <?php else: ?>
              <span class="album-index-placeholder" aria-hidden="true"></span>
            <?php endif; ?>
          </div>
          <div class="album-index-meta">
            <strong><?= Str::e((string) $row['title']) ?></strong>
            <span><?= (int) ($row['media_count'] ?? 0) ?> photos · <?= !empty($row['is_published']) ? 'Published' : 'Draft' ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
