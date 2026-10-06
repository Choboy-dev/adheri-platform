<?php

use Adheri\Security\Csrf;
use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var list<array<string, mixed>> $items */
$count = count($items);
?>
<div class="media-studio" data-media-studio>
  <header class="media-studio-head">
    <div class="media-studio-count" aria-live="polite">
      <strong><?= $count ?></strong>
      <span><?= $count === 1 ? 'asset' : 'assets' ?></span>
    </div>
  </header>

  <form
    method="post"
    action="<?= Str::e(Url::to('admin/media/upload')) ?>"
    enctype="multipart/form-data"
    class="media-upload-shell"
    data-media-upload
  >
    <?= Csrf::field() ?>

    <div class="media-dropzone" data-dropzone tabindex="0" role="button" aria-controls="files" aria-label="Choose images to upload">
      <input
        class="media-file-input"
        type="file"
        id="files"
        name="files[]"
        accept="image/jpeg,image/png,image/webp,image/gif"
        multiple
        required
        data-file-input
      >
      <div class="media-dropzone-inner">
        <span class="media-dropzone-icon" aria-hidden="true">
          <svg viewBox="0 0 48 48" width="40" height="40"><path fill="currentColor" d="M10 8h28a4 4 0 0 1 4 4v24a4 4 0 0 1-4 4H10a4 4 0 0 1-4-4V12a4 4 0 0 1 4-4zm0 2a2 2 0 0 0-2 2v18.17l7.59-7.58a2 2 0 0 1 2.82 0L28 32.17l4.59-4.58a2 2 0 0 1 2.82 0L40 31.17V12a2 2 0 0 0-2-2H10zm8 6a3 3 0 1 1 0 6 3 3 0 0 1 0-6z"/></svg>
        </span>
        <p class="media-dropzone-title">Drop images here</p>
        <p class="media-dropzone-sub">or <span>browse</span> · JPEG, PNG, WebP, GIF · up to 8 MB each</p>
        <ul class="media-file-list" data-file-list hidden></ul>
      </div>
    </div>

    <div class="media-upload-meta">
      <label class="media-field" for="alt_text">
        <span>Alt text</span>
        <input type="text" id="alt_text" name="alt_text" placeholder="Describe the images for accessibility">
      </label>
      <button class="btn media-upload-btn" type="submit" data-upload-btn disabled>
        Upload
        <span class="btn-icon" aria-hidden="true">↗</span>
      </button>
    </div>
  </form>

  <section class="media-library-shell" aria-label="Uploaded media">
    <?php if ($items === []): ?>
      <div class="media-empty">
        <div class="media-empty-visual" aria-hidden="true">
          <span></span><span></span><span></span>
        </div>
        <h3>No images yet</h3>
        <p>Drop files into the zone above to start building ADHERI’s media library.</p>
      </div>
    <?php else: ?>
      <div class="media-masonry">
        <?php foreach ($items as $row): ?>
          <?php
            $src = Url::to(ltrim((string) $row['url_path'], '/'));
            $name = (string) $row['original_name'];
            $alt = (string) ($row['alt_text'] ?? $name);
            $w = (int) ($row['width'] ?? 0);
            $h = (int) ($row['height'] ?? 0);
            $dims = ($w > 0 && $h > 0) ? $w . '×' . $h : '';
            $size = (int) ($row['size_bytes'] ?? 0);
            $sizeLabel = $size > 0 ? (round($size / 1024) . ' KB') : '';
          ?>
          <article class="media-tile">
            <div class="media-tile-frame">
              <img src="<?= Str::e($src) ?>" alt="<?= Str::e($alt) ?>" loading="lazy" decoding="async">
              <div class="media-tile-veil">
                <a class="media-tile-view" href="<?= Str::e($src) ?>" target="_blank" rel="noopener">Open</a>
                <form method="post" action="<?= Str::e(Url::to('admin/media/' . (int) $row['id'] . '/delete')) ?>" onsubmit="return confirm('Delete this file? Pages using it may break.');">
                  <?= Csrf::field() ?>
                  <button class="media-tile-delete" type="submit">Delete</button>
                </form>
              </div>
            </div>
            <div class="media-tile-meta">
              <strong title="<?= Str::e($name) ?>"><?= Str::e(Str::excerpt($name, 28)) ?></strong>
              <span>
                #<?= (int) $row['id'] ?>
                <?php if ($dims !== ''): ?> · <?= Str::e($dims) ?><?php endif; ?>
                <?php if ($sizeLabel !== ''): ?> · <?= Str::e($sizeLabel) ?><?php endif; ?>
              </span>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<script>
(() => {
  const form = document.querySelector('[data-media-upload]');
  const zone = document.querySelector('[data-dropzone]');
  const input = document.querySelector('[data-file-input]');
  const list = document.querySelector('[data-file-list]');
  const btn = document.querySelector('[data-upload-btn]');
  if (!form || !zone || !input || !list || !btn) return;

  const setButtonLabel = (label) => {
    btn.replaceChildren();
    btn.append(document.createTextNode(label + ' '));
    const icon = document.createElement('span');
    icon.className = 'btn-icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = '↗';
    btn.appendChild(icon);
  };

  const sync = () => {
    const files = [...(input.files || [])];
    list.innerHTML = '';
    if (files.length === 0) {
      list.hidden = true;
      btn.disabled = true;
      setButtonLabel('Upload');
      return;
    }
    list.hidden = false;
    files.forEach((file) => {
      const li = document.createElement('li');
      li.textContent = file.name + ' · ' + Math.max(1, Math.round(file.size / 1024)) + ' KB';
      list.appendChild(li);
    });
    btn.disabled = false;
    setButtonLabel(files.length === 1 ? 'Upload 1 image' : 'Upload ' + files.length + ' images');
  };

  const openPicker = () => input.click();
  zone.addEventListener('click', openPicker);
  zone.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      openPicker();
    }
  });

  ;['dragenter', 'dragover'].forEach((evt) => {
    zone.addEventListener(evt, (e) => {
      e.preventDefault();
      zone.classList.add('is-dragging');
    });
  });
  ;['dragleave', 'drop'].forEach((evt) => {
    zone.addEventListener(evt, (e) => {
      e.preventDefault();
      zone.classList.remove('is-dragging');
    });
  });
  zone.addEventListener('drop', (e) => {
    const files = e.dataTransfer?.files;
    if (!files?.length) return;
    const dt = new DataTransfer();
    [...files].forEach((f) => {
      if (f.type.startsWith('image/')) dt.items.add(f);
    });
    input.files = dt.files;
    sync();
  });
  input.addEventListener('change', sync);
  input.addEventListener('click', (e) => e.stopPropagation());
})();
</script>
