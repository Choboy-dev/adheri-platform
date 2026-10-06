<?php

use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var list<array<string, mixed>> $posts */
?>
<section class="page-hero">
  <div class="wrap">
    <p class="eyebrow">Blog</p>
    <h1>Articles</h1>
    <p class="lead">Thoughts on rights, GBV, climate, and community change.</p>
  </div>
</section>

<section class="section">
  <div class="wrap post-list">
    <?php foreach ($posts as $post): ?>
      <a class="post-item" href="<?= Str::e(Url::to('blog/' . $post['slug'])) ?>">
        <div class="post-thumb" aria-hidden="true"></div>
        <div>
          <div class="post-meta"><?= Str::e((string) ($post['category_name'] ?? 'Articles')) ?> · <?= Str::e(substr((string) ($post['published_at'] ?? ''), 0, 10)) ?></div>
          <h2><?= Str::e((string) $post['title']) ?></h2>
          <p><?= Str::e((string) ($post['excerpt'] ?? '')) ?></p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
