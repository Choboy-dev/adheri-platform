<?php

use Adheri\Security\Csrf;
use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var list<array<string, mixed>> $items */

$photoUrl = static function (array $row): string {
    $path = trim((string) ($row['photo_path'] ?? ''));
    if ($path === '') {
        return '';
    }
    return str_starts_with($path, 'uploads/')
        ? Url::to(ltrim($path, '/'))
        : Url::asset($path);
};
?>
<div class="testimonial-admin">
  <header class="testimonial-admin-head">
    <a class="btn testimonial-admin-new" href="<?= Str::e(Url::to('admin/testimonials/create')) ?>">
      New testimonial
      <span class="btn-icon" aria-hidden="true">↗</span>
    </a>
  </header>

  <?php if ($items === []): ?>
    <div class="media-empty">
      <div class="media-empty-visual" aria-hidden="true"><span></span><span></span><span></span></div>
      <h3>No testimonials yet</h3>
      <p>Add the first voice to show on the public home page.</p>
    </div>
  <?php else: ?>
    <div class="testimonial-admin-grid">
      <?php foreach ($items as $row): ?>
        <?php
          $img = $photoUrl($row);
          $published = !empty($row['is_published']);
          $initial = strtoupper(substr((string) $row['person_name'], 0, 1));
        ?>
        <article class="testimonial-admin-card">
          <div class="testimonial-admin-shell">
            <div class="testimonial-admin-core">
              <div class="testimonial-admin-top">
                <span class="testimonial-admin-tag <?= $published ? 'is-live' : 'is-draft' ?>">
                  <?= $published ? 'Published' : 'Draft' ?>
                </span>
                <span class="testimonial-admin-order">#<?= (int) $row['sort_order'] ?></span>
              </div>

              <blockquote class="testimonial-admin-quote">
                <p><?= Str::e(Str::excerpt((string) $row['quote'], 160)) ?></p>
              </blockquote>

              <div class="testimonial-admin-person">
                <?php if ($img !== ''): ?>
                  <img class="testimonial-admin-avatar" src="<?= Str::e($img) ?>" alt="" loading="lazy" decoding="async">
                <?php else: ?>
                  <span class="testimonial-admin-avatar is-fallback" aria-hidden="true"><?= Str::e($initial) ?></span>
                <?php endif; ?>
                <div>
                  <strong><?= Str::e((string) $row['person_name']) ?></strong>
                  <?php if (!empty($row['location'])): ?>
                    <span><?= Str::e((string) $row['location']) ?></span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="testimonial-admin-actions">
                <a class="btn btn-ghost btn-sm" href="<?= Str::e(Url::to('admin/testimonials/edit/' . (int) $row['id'])) ?>">Edit</a>
                <form method="post" action="<?= Str::e(Url::to('admin/testimonials/delete/' . (int) $row['id'])) ?>" onsubmit="return confirm('Delete this testimonial?');">
                  <?= Csrf::field() ?>
                  <button type="submit" class="testimonial-admin-delete">Delete</button>
                </form>
              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
