<?php

use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var array<string, mixed> $album */
/** @var list<array<string, mixed>> $media */
?>
<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow"><a href="<?= Str::e(Url::to('gallery')) ?>">Gallery</a></p>
    <h1><?= Str::e((string) $album['title']) ?></h1>
    <?php if (!empty($album['description'])): ?>
      <p class="lead"><?= Str::e((string) $album['description']) ?></p>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="wrap album-bento">
    <?php if ($media === []): ?>
      <p class="lead">This album has no published media yet.</p>
    <?php else: ?>
      <?php foreach ($media as $item): ?>
        <figure class="album-tile">
          <img src="<?= Str::e(Url::to(ltrim((string) $item['url_path'], '/'))) ?>" alt="<?= Str::e((string) ($item['caption'] ?: $item['alt_text'] ?: $album['title'])) ?>" style="width:100%;height:100%;object-fit:cover;min-height:16rem">
          <?php if (!empty($item['caption'])): ?>
            <figcaption class="album-tile-body"><p><?= Str::e((string) $item['caption']) ?></p></figcaption>
          <?php endif; ?>
        </figure>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>
