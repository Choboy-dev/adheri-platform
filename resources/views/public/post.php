<?php

use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var array<string, mixed> $post */
?>
<section class="section" style="padding-top:8rem">
  <div class="wrap prose">
    <p class="post-meta"><a href="<?= Str::e(Url::to('blog')) ?>">Blog</a> · <?= Str::e((string) ($post['category_name'] ?? 'Articles')) ?> · <?= Str::e(substr((string) ($post['published_at'] ?? ''), 0, 10)) ?></p>
    <h1><?= Str::e((string) $post['title']) ?></h1>
    <?= $post['body'] ?>
  </div>
</section>
