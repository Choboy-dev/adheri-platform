<?php

use Adheri\Security\Csrf;
use Adheri\Support\Str;
use Adheri\Support\Url;
use Adheri\View;

/** @var array<string, mixed>|null $item */
$isEdit = $item !== null;
$action = $isEdit
    ? Url::to('admin/testimonials/edit/' . (int) $item['id'])
    : Url::to('admin/testimonials/create');
$photoPath = trim((string) ($item['photo_path'] ?? ''));
$photoPreview = '';
if ($photoPath !== '') {
    $photoPreview = str_starts_with($photoPath, 'uploads/')
        ? Url::to(ltrim($photoPath, '/'))
        : Url::asset($photoPath);
}
$hasPhoto = $photoPreview !== '';
?>
<link rel="stylesheet" href="<?= Str::e(Url::asset('css/docs-editor.css')) ?>">

<form
  method="post"
  action="<?= Str::e($action) ?>"
  enctype="multipart/form-data"
  class="testimonial-studio"
  data-featured-image
>
  <?= Csrf::field() ?>
  <input type="hidden" name="photo_path" value="<?= Str::e($photoPath) ?>">

  <div class="docs-chrome testimonial-chrome">
    <div class="docs-menubar">
      <div class="docs-menubar-left">
        <a class="docs-back" href="<?= Str::e(Url::to('admin/testimonials')) ?>" aria-label="Back to testimonials">←</a>
        <input
          class="docs-title-input"
          type="text"
          name="person_name"
          required
          placeholder="Person name"
          value="<?= Str::e((string) ($item['person_name'] ?? '')) ?>"
          autocomplete="off"
        >
      </div>
      <div class="docs-menubar-right">
        <button class="btn btn-ghost docs-save-draft" type="submit" name="is_published" value="0">Save as draft</button>
        <button class="btn docs-publish" type="submit" name="is_published" value="1">Publish</button>
      </div>
    </div>

    <div class="testimonial-workspace">
      <div class="testimonial-main">
        <section class="testimonial-panel">
          <label class="testimonial-label" for="quote">Quote</label>
          <textarea
            id="quote"
            name="quote"
            class="testimonial-quote-input"
            rows="8"
            required
            placeholder="What did they say about ADHERI?"
          ><?= Str::e((string) ($item['quote'] ?? '')) ?></textarea>
        </section>
      </div>

      <aside class="testimonial-side">
        <section class="testimonial-panel">
          <label class="testimonial-label" for="location">Location</label>
          <input
            id="location"
            name="location"
            type="text"
            placeholder="City, Nigeria"
            value="<?= Str::e((string) ($item['location'] ?? '')) ?>"
          >

          <label class="testimonial-label" for="sort_order">Sort order</label>
          <input id="sort_order" name="sort_order" type="number" value="<?= (int) ($item['sort_order'] ?? 0) ?>">

          <div class="featured-image-field">
            <div class="featured-image-label">Photo</div>
            <div class="featured-image-box<?= $hasPhoto ? ' has-preview' : '' ?>">
              <div class="featured-image-preview" data-featured-preview <?= $hasPhoto ? '' : 'hidden' ?>>
                <?php if ($hasPhoto): ?>
                  <img src="<?= Str::e($photoPreview) ?>" alt="" data-featured-img>
                <?php else: ?>
                  <img alt="" data-featured-img hidden>
                <?php endif; ?>
              </div>
              <label class="featured-image-drop" for="photo" data-featured-empty <?= $hasPhoto ? 'hidden' : '' ?>>
                <span class="featured-image-icon" aria-hidden="true"><?= View::render('admin/partials/docs-icons', ['icon' => 'featured']) ?></span>
                <span class="featured-image-cta">Set photo</span>
                <span class="featured-image-hint">JPEG, PNG, WebP, or GIF · max 8 MB</span>
              </label>
              <input id="photo" class="featured-image-input" type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif" data-featured-input>
            </div>
            <div class="featured-image-actions" data-featured-actions <?= $hasPhoto ? '' : 'hidden' ?>>
              <label class="featured-image-replace" for="photo">Replace photo</label>
              <button type="button" class="featured-image-remove" data-featured-remove>Remove photo</button>
            </div>
            <input type="hidden" name="remove_photo" value="0" data-featured-remove-flag>
          </div>
        </section>
      </aside>
    </div>
  </div>
</form>

<script>
(() => {
  const root = document.querySelector('[data-featured-image]');
  if (!root) return;
  const input = root.querySelector('[data-featured-input]');
  const preview = root.querySelector('[data-featured-preview]');
  const img = root.querySelector('[data-featured-img]');
  const empty = root.querySelector('[data-featured-empty]');
  const actions = root.querySelector('[data-featured-actions]');
  const removeBtn = root.querySelector('[data-featured-remove]');
  const removeFlag = root.querySelector('[data-featured-remove-flag]');
  const box = root.querySelector('.featured-image-box');
  let objectUrl = null;

  const showPreview = (url) => {
    if (!img || !preview || !empty || !actions || !box) return;
    img.src = url;
    img.hidden = false;
    preview.hidden = false;
    empty.hidden = true;
    actions.hidden = false;
    box.classList.add('has-preview');
    if (removeFlag) removeFlag.value = '0';
  };

  const clearPreview = () => {
    if (!img || !preview || !empty || !actions || !box) return;
    if (objectUrl) {
      URL.revokeObjectURL(objectUrl);
      objectUrl = null;
    }
    img.removeAttribute('src');
    img.hidden = true;
    preview.hidden = true;
    empty.hidden = false;
    actions.hidden = true;
    box.classList.remove('has-preview');
    if (input) input.value = '';
    if (removeFlag) removeFlag.value = '1';
  };

  input?.addEventListener('change', () => {
    const file = input.files?.[0];
    if (!file) return;
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl = URL.createObjectURL(file);
    showPreview(objectUrl);
  });

  removeBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    clearPreview();
  });
})();
</script>
