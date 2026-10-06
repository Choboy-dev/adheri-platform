<?php

use Adheri\Security\Csrf;
use Adheri\Support\Str;
use Adheri\Support\Url;
use Adheri\View;

/** @var array<string, mixed>|null $album */
/** @var list<array<string, mixed>> $mediaItems */
/** @var list<array<string, mixed>> $library */
$isNew = $album === null;
$albumId = $isNew ? 0 : (int) $album['id'];
$action = $isNew ? Url::to('admin/albums') : Url::to('admin/albums/' . $albumId);
$coverId = (int) ($album['cover_id'] ?? 0);
$coverUrl = null;
foreach ($mediaItems as $m) {
    if ((int) $m['id'] === $coverId) {
        $coverUrl = Url::to(ltrim((string) $m['url_path'], '/'));
        break;
    }
}
if ($coverUrl === null && $coverId > 0) {
    foreach ($library as $m) {
        if ((int) $m['id'] === $coverId) {
            $coverUrl = Url::to(ltrim((string) $m['url_path'], '/'));
            break;
        }
    }
}
$attachedIds = [];
foreach ($mediaItems as $m) {
    $attachedIds[(int) $m['id']] = true;
}
?>
<link rel="stylesheet" href="<?= Str::e(Url::asset('css/docs-editor.css')) ?>">

<form method="post" action="<?= Str::e($action) ?>" class="album-studio" data-album-studio>
  <?= Csrf::field() ?>

  <div class="docs-chrome album-chrome">
    <div class="docs-menubar">
      <div class="docs-menubar-left">
        <a class="docs-back" href="<?= Str::e(Url::to('admin/albums')) ?>" aria-label="Back to albums">←</a>
        <input
          class="docs-title-input"
          type="text"
          name="title"
          required
          placeholder="Album title"
          value="<?= Str::e((string) ($album['title'] ?? '')) ?>"
          autocomplete="off"
        >
      </div>
      <div class="docs-menubar-right">
        <button class="btn btn-ghost docs-save-draft" type="submit" name="is_published" value="0">Save as draft</button>
        <button class="btn docs-publish" type="submit" name="is_published" value="1">Publish</button>
      </div>
    </div>

    <div class="album-workspace">
      <div class="album-main">
        <section class="album-panel">
          <label class="album-label" for="description">Description</label>
          <textarea
            id="description"
            name="description"
            class="album-description"
            rows="4"
            placeholder="What was this moment? Who was there? What should visitors feel?"
          ><?= Str::e((string) ($album['description'] ?? '')) ?></textarea>
        </section>

        <?php if ($isNew): ?>
          <section class="album-panel album-hint">
            <div class="media-empty-visual" aria-hidden="true"><span></span><span></span><span></span></div>
            <h3>Photos come next</h3>
            <p>Publish or save the album first, then you’ll attach images from the media library on this same screen.</p>
          </section>
        <?php else: ?>
          <section class="album-panel">
            <div class="album-section-head">
              <h2>In this album</h2>
              <span class="muted"><?= count($mediaItems) ?> <?= count($mediaItems) === 1 ? 'photo' : 'photos' ?></span>
            </div>

            <?php if ($mediaItems === []): ?>
              <div class="album-empty-inline">
                <p>No photos yet. Choose images from the library below, then save.</p>
              </div>
            <?php else: ?>
              <div class="album-attached-grid">
                <?php foreach ($mediaItems as $item): ?>
                  <?php
                    $mid = (int) $item['id'];
                    $src = Url::to(ltrim((string) $item['url_path'], '/'));
                    $isCover = $coverId === $mid;
                  ?>
                  <article class="album-shot<?= $isCover ? ' is-cover' : '' ?>">
                    <div class="album-shot-frame">
                      <img src="<?= Str::e($src) ?>" alt="<?= Str::e((string) ($item['alt_text'] ?? $item['original_name'])) ?>" loading="lazy">
                      <?php if ($isCover): ?><span class="album-cover-badge">Cover</span><?php endif; ?>
                    </div>
                    <label class="album-set-cover">
                      <input type="radio" name="cover_id" value="<?= $mid ?>" <?= $isCover ? 'checked' : '' ?>>
                      <span>Use as cover</span>
                    </label>
                    <label class="album-field-mini">
                      <span>Caption</span>
                      <input type="text" name="media_caption[<?= $mid ?>]" value="<?= Str::e((string) ($item['caption'] ?? '')) ?>" placeholder="Optional caption">
                    </label>
                    <label class="album-field-mini">
                      <span>Order</span>
                      <input type="number" name="media_sort[<?= $mid ?>]" value="<?= (int) ($item['sort_order'] ?? 0) ?>">
                    </label>
                    <button
                      class="album-detach"
                      type="submit"
                      formaction="<?= Str::e(Url::to('admin/albums/' . $albumId . '/media/' . $mid . '/detach')) ?>"
                      formmethod="post"
                      onclick="return confirm('Remove this photo from the album?');"
                    >Remove</button>
                  </article>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </section>

          <section class="album-panel">
            <div class="album-section-head">
              <h2>Add from media library</h2>
              <a class="album-link" href="<?= Str::e(Url::to('admin/media')) ?>">Open media library</a>
            </div>
            <?php if ($library === []): ?>
              <p class="muted">Library is empty. <a href="<?= Str::e(Url::to('admin/media')) ?>">Upload images</a> first.</p>
            <?php else: ?>
              <div class="album-library-grid">
                <?php foreach ($library as $lib): ?>
                  <?php
                    $lid = (int) $lib['id'];
                    $already = isset($attachedIds[$lid]);
                    $src = Url::to(ltrim((string) $lib['url_path'], '/'));
                  ?>
                  <label class="album-lib-card<?= $already ? ' is-attached' : '' ?>">
                    <?php if ($already): ?>
                      <span class="album-lib-state">In album</span>
                    <?php else: ?>
                      <input type="checkbox" name="attach_media_ids[]" value="<?= $lid ?>">
                    <?php endif; ?>
                    <img src="<?= Str::e($src) ?>" alt="" loading="lazy">
                  </label>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </section>
        <?php endif; ?>
      </div>

      <aside class="album-side">
        <div class="album-side-card">
          <h2>Album details</h2>

          <label for="slug">Slug</label>
          <input id="slug" name="slug" value="<?= Str::e((string) ($album['slug'] ?? '')) ?>" placeholder="auto-from-title">

          <label for="event_date">Event date</label>
          <input id="event_date" type="date" name="event_date" value="<?= Str::e((string) ($album['event_date'] ?? '')) ?>">

          <label for="sort_order">Sort order</label>
          <input id="sort_order" type="number" name="sort_order" value="<?= (int) ($album['sort_order'] ?? 0) ?>">

          <div class="featured-image-field" style="margin-top:1rem">
            <div class="featured-image-label">Cover image</div>
            <p class="featured-image-help">Pick “Use as cover” on a photo in the album, or choose one below.</p>
            <div class="featured-image-box<?= $coverUrl ? ' has-preview' : '' ?>" data-album-cover-box>
              <?php if ($coverUrl): ?>
                <div class="featured-image-preview">
                  <img src="<?= Str::e($coverUrl) ?>" alt="Cover preview" data-album-cover-img>
                </div>
              <?php else: ?>
                <div class="featured-image-drop" style="pointer-events:none">
                  <span class="featured-image-icon" aria-hidden="true"><?= View::render('admin/partials/docs-icons', ['icon' => 'featured']) ?></span>
                  <span class="featured-image-cta">No cover yet</span>
                  <span class="featured-image-hint">Select a photo as cover</span>
                </div>
              <?php endif; ?>
            </div>
            <?php if ($coverId > 0 && $mediaItems === []): ?>
              <input type="hidden" name="cover_id" value="<?= $coverId ?>" data-cover-fallback>
            <?php endif; ?>
          </div>
        </div>

        <?php if (!$isNew): ?>
          <button
            class="btn btn-danger album-delete"
            type="submit"
            formaction="<?= Str::e(Url::to('admin/albums/' . $albumId . '/delete')) ?>"
            formmethod="post"
            onclick="return confirm('Delete this album permanently?');"
          >Delete album</button>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</form>

<script>
(() => {
  const radios = document.querySelectorAll('input[name="cover_id"][type="radio"]');
  const fallback = document.querySelector('[data-cover-fallback]');
  const box = document.querySelector('[data-album-cover-box]');
  radios.forEach((radio) => {
    radio.addEventListener('change', () => {
      if (fallback) fallback.remove();
      const card = radio.closest('.album-shot');
      const img = card?.querySelector('img');
      if (!box || !img) return;
      box.classList.add('has-preview');
      box.innerHTML = '<div class="featured-image-preview"><img src="' + img.src + '" alt="Cover preview"></div>';
      document.querySelectorAll('.album-shot').forEach((el) => el.classList.remove('is-cover'));
      card?.classList.add('is-cover');
    });
  });
})();
</script>
