<?php

use Adheri\Security\Csrf;
use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var list<array<string, mixed>> $items */

$fallbackImages = [
    'advocacy' => 'img/programs/advocacy.jpg',
    'counselling' => 'img/programs/counselling.jpg',
    'empowerment' => 'img/programs/empowerment.jpg',
    'climate-change' => 'img/programs/climate.jpg',
    'media' => 'img/programs/media.jpg',
    'rehab-support' => 'img/programs/rehab.jpg',
    'artivism' => 'img/programs/artivism.jpg',
];
?>
<div class="program-admin">
  <header class="program-admin-head">
    <div>
      <p class="program-admin-lead">Programs power the public Programs page and home highlights.</p>
    </div>
    <a class="btn program-admin-new" href="<?= Str::e(Url::to('admin/programs/create')) ?>">
      New program
      <span class="btn-icon" aria-hidden="true">↗</span>
    </a>
  </header>

  <?php if ($items === []): ?>
    <div class="media-empty">
      <div class="media-empty-visual" aria-hidden="true"><span></span><span></span><span></span></div>
      <h3>No programs yet</h3>
      <p>Create the first program to show ADHERI’s work on the site.</p>
    </div>
  <?php else: ?>
    <div class="program-admin-grid">
      <?php foreach ($items as $row): ?>
        <?php
          $slug = (string) $row['slug'];
          $imagePath = trim((string) ($row['image_url'] ?? ''));
          if ($imagePath !== '') {
              $img = Url::to(ltrim($imagePath, '/'));
          } elseif (isset($fallbackImages[$slug])) {
              $img = Url::asset($fallbackImages[$slug]);
          } else {
              $img = '';
          }
          $published = !empty($row['is_published']);
          $onHome = !empty($row['featured_on_home']);
        ?>
        <article class="program-admin-card">
          <a class="program-admin-cover" href="<?= Str::e(Url::to('admin/programs/edit/' . (int) $row['id'])) ?>">
            <?php if ($img !== ''): ?>
              <img src="<?= Str::e($img) ?>" alt="" loading="lazy" decoding="async">
            <?php else: ?>
              <span class="program-admin-cover-fallback" aria-hidden="true"></span>
            <?php endif; ?>
            <span class="program-admin-order">#<?= (int) $row['sort_order'] ?></span>
          </a>
          <div class="program-admin-body">
            <div class="program-admin-tags">
              <span class="program-admin-tag <?= $published ? 'is-live' : 'is-draft' ?>">
                <?= $published ? 'Published' : 'Draft' ?>
              </span>
              <?php if ($onHome): ?>
                <span class="program-admin-tag is-home">On home</span>
              <?php endif; ?>
            </div>
            <h3><?= Str::e((string) $row['title']) ?></h3>
            <?php if (!empty($row['summary'])): ?>
              <p><?= Str::e(Str::excerpt((string) $row['summary'], 110)) ?></p>
            <?php endif; ?>
            <div class="program-admin-actions">
              <a class="btn btn-ghost btn-sm" href="<?= Str::e(Url::to('admin/programs/edit/' . (int) $row['id'])) ?>">Edit</a>
              <form method="post" action="<?= Str::e(Url::to('admin/programs/delete/' . (int) $row['id'])) ?>" onsubmit="return confirm('Delete this program?');">
                <?= Csrf::field() ?>
                <button type="submit" class="program-admin-delete">Delete</button>
              </form>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
