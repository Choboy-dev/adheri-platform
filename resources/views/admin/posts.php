<?php

use Adheri\Security\Csrf;
use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var list<array<string, mixed>> $items */
/** @var int $page */
/** @var int $totalPages */
/** @var int $total */

$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$total = (int) ($total ?? count($items));

$statusLabel = static function (string $status): string {
    return match ($status) {
        'published' => 'Published',
        'draft' => 'Draft',
        default => ucfirst($status),
    };
};

$formatDate = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '';
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return $value;
    }
    return date('j M Y', $ts);
};

$pageUrl = static function (int $p): string {
    return Url::to('admin/posts' . ($p > 1 ? '?page=' . $p : ''));
};
?>
<div class="blog-admin">
  <header class="blog-admin-head">
    <a class="btn blog-admin-new" href="<?= Str::e(Url::to('admin/posts/create')) ?>">
      New post
      <span class="btn-icon" aria-hidden="true">↗</span>
    </a>
  </header>

  <?php if ($items === []): ?>
    <div class="media-empty">
      <div class="media-empty-visual" aria-hidden="true"><span></span><span></span><span></span></div>
      <h3>No posts yet</h3>
      <p>Create the first article to appear on the public Blog.</p>
    </div>
  <?php else: ?>
    <div class="blog-admin-grid">
      <?php foreach ($items as $row): ?>
        <?php
          $status = (string) ($row['status'] ?? 'draft');
          $published = $status === 'published';
          $cover = trim((string) ($row['cover_url'] ?? ''));
          $coverSrc = $cover !== '' ? Url::to(ltrim($cover, '/')) : '';
          $updated = $formatDate((string) ($row['updated_at'] ?? ''));
          $excerpt = trim((string) ($row['excerpt'] ?? ''));
        ?>
        <article class="blog-admin-card">
          <a class="blog-admin-cover" href="<?= Str::e(Url::to('admin/posts/edit/' . (int) $row['id'])) ?>">
            <?php if ($coverSrc !== ''): ?>
              <img src="<?= Str::e($coverSrc) ?>" alt="" loading="lazy" decoding="async">
            <?php else: ?>
              <span class="blog-admin-cover-fallback" aria-hidden="true"></span>
            <?php endif; ?>
          </a>
          <div class="blog-admin-body">
            <div class="blog-admin-meta">
              <span class="blog-admin-tag <?= $published ? 'is-live' : 'is-draft' ?>">
                <?= Str::e($statusLabel($status)) ?>
              </span>
              <?php if (!empty($row['category_name'])): ?>
                <span class="blog-admin-cat"><?= Str::e((string) $row['category_name']) ?></span>
              <?php endif; ?>
            </div>
            <h3>
              <a href="<?= Str::e(Url::to('admin/posts/edit/' . (int) $row['id'])) ?>">
                <?= Str::e((string) $row['title']) ?>
              </a>
            </h3>
            <?php if ($excerpt !== ''): ?>
              <p><?= Str::e(Str::excerpt($excerpt, 90)) ?></p>
            <?php endif; ?>
            <?php if ($updated !== ''): ?>
              <span class="blog-admin-date"><?= Str::e($updated) ?></span>
            <?php endif; ?>
            <div class="blog-admin-actions">
              <a class="btn btn-ghost btn-sm" href="<?= Str::e(Url::to('admin/posts/edit/' . (int) $row['id'])) ?>">Edit</a>
              <form method="post" action="<?= Str::e(Url::to('admin/posts/delete/' . (int) $row['id'])) ?>" onsubmit="return confirm('Delete this post?');">
                <?= Csrf::field() ?>
                <button type="submit" class="blog-admin-delete">Delete</button>
              </form>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
      <nav class="blog-admin-pager" aria-label="Blog pages">
        <?php if ($page > 1): ?>
          <a class="btn btn-ghost" href="<?= Str::e($pageUrl($page - 1)) ?>">Previous</a>
        <?php else: ?>
          <span class="btn btn-ghost is-disabled" aria-disabled="true">Previous</span>
        <?php endif; ?>

        <p class="blog-admin-pager-status">Page <?= $page ?> of <?= $totalPages ?> · <?= $total ?> posts</p>

        <?php if ($page < $totalPages): ?>
          <a class="btn" href="<?= Str::e($pageUrl($page + 1)) ?>">Next</a>
        <?php else: ?>
          <span class="btn is-disabled" aria-disabled="true">Next</span>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</div>
