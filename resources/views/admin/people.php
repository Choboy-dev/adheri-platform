<?php

use Adheri\Security\Csrf;
use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var list<array<string, mixed>> $items */

$groupLabels = [
    'board' => 'Board of Trustees',
    'youth_advisory' => 'Youth Advisory',
];

$grouped = [];
foreach ($items as $row) {
    $key = (string) ($row['person_group'] ?? 'board');
    $grouped[$key][] = $row;
}

$photoSrc = static function (array $row): string {
    $path = trim((string) ($row['photo_url'] ?? ''));
    if ($path === '') {
        return '';
    }
    return str_starts_with($path, 'http')
        ? $path
        : Url::to(ltrim($path, '/'));
};
?>
<div class="people-admin">
  <header class="people-admin-head">
    <div>
      <p class="people-admin-lead">Board and youth advisors shown on the public About page.</p>
    </div>
    <a class="btn people-admin-new" href="<?= Str::e(Url::to('admin/people/create')) ?>">
      New person
      <span class="btn-icon" aria-hidden="true">↗</span>
    </a>
  </header>

  <?php if ($items === []): ?>
    <div class="media-empty">
      <div class="media-empty-visual" aria-hidden="true"><span></span><span></span><span></span></div>
      <h3>No people yet</h3>
      <p>Add trustees or youth advisors to populate About.</p>
    </div>
  <?php else: ?>
    <?php foreach ($groupLabels as $groupKey => $groupTitle): ?>
      <?php if (empty($grouped[$groupKey])) {
          continue;
      } ?>
      <section class="people-admin-group">
        <h2><?= Str::e($groupTitle) ?></h2>
        <div class="people-admin-grid">
          <?php foreach ($grouped[$groupKey] as $row): ?>
            <?php
              $img = $photoSrc($row);
              $published = !empty($row['is_published']);
              $initial = strtoupper(substr((string) $row['name'], 0, 1));
            ?>
            <article class="people-admin-card">
              <a class="people-admin-photo" href="<?= Str::e(Url::to('admin/people/edit/' . (int) $row['id'])) ?>">
                <?php if ($img !== ''): ?>
                  <img src="<?= Str::e($img) ?>" alt="" loading="lazy" decoding="async">
                <?php else: ?>
                  <span class="people-admin-photo-fallback" aria-hidden="true"><?= Str::e($initial) ?></span>
                <?php endif; ?>
              </a>
              <div class="people-admin-body">
                <div class="people-admin-tags">
                  <span class="people-admin-tag <?= $published ? 'is-live' : 'is-draft' ?>">
                    <?= $published ? 'Published' : 'Draft' ?>
                  </span>
                  <span class="people-admin-order">#<?= (int) $row['sort_order'] ?></span>
                </div>
                <h3><?= Str::e((string) $row['name']) ?></h3>
                <?php if (!empty($row['role_title'])): ?>
                  <p class="people-admin-role"><?= Str::e((string) $row['role_title']) ?></p>
                <?php endif; ?>
                <?php if (!empty($row['bio'])): ?>
                  <p class="people-admin-bio"><?= Str::e(Str::excerpt((string) $row['bio'], 100)) ?></p>
                <?php endif; ?>
                <div class="people-admin-actions">
                  <a class="btn btn-ghost btn-sm" href="<?= Str::e(Url::to('admin/people/edit/' . (int) $row['id'])) ?>">Edit</a>
                  <form method="post" action="<?= Str::e(Url::to('admin/people/delete/' . (int) $row['id'])) ?>" onsubmit="return confirm('Delete this person?');">
                    <?= Csrf::field() ?>
                    <button type="submit" class="people-admin-delete">Delete</button>
                  </form>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
