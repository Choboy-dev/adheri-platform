<?php

use Adheri\Security\Csrf;
use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var array<string, mixed>|null $item */
/** @var string|null $imageUrl */
$isNew = $item === null;
$itemId = $isNew ? 0 : (int) $item['id'];
$action = $isNew ? Url::to('admin/announcements') : Url::to('admin/announcements/' . $itemId);

$starts = '';
if (!empty($item['starts_at'])) {
    $starts = date('Y-m-d\TH:i', strtotime((string) $item['starts_at']));
}
$ends = '';
if (!empty($item['ends_at'])) {
    $ends = date('Y-m-d\TH:i', strtotime((string) $item['ends_at']));
}
?>
<p><a href="<?= Str::e(Url::to('admin/announcements')) ?>">← Announcements</a></p>

<div class="panel">
  <form method="post" action="<?= Str::e($action) ?>" enctype="multipart/form-data" class="admin-form">
    <?= Csrf::field() ?>
    <label for="title">Title</label>
    <input type="text" id="title" name="title" value="<?= Str::e((string) ($item['title'] ?? '')) ?>">

    <label for="link_url">Link URL (optional)</label>
    <input type="url" id="link_url" name="link_url" value="<?= Str::e((string) ($item['link_url'] ?? '')) ?>" placeholder="https://">

    <label for="image">Flyer image <?= $isNew ? '(required)' : '(upload to replace)' ?></label>
    <?php if ($imageUrl): ?>
      <p><img class="thumb-md" src="<?= Str::e(Url::to(ltrim($imageUrl, '/'))) ?>" alt="Current flyer"></p>
    <?php endif; ?>
    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif" <?= $isNew ? 'required' : '' ?>>

    <div class="form-grid">
      <div>
        <label for="sort_order">Sort order</label>
        <input type="number" id="sort_order" name="sort_order" value="<?= (int) ($item['sort_order'] ?? 0) ?>">
      </div>
      <div>
        <label for="starts_at">Starts at</label>
        <input type="datetime-local" id="starts_at" name="starts_at" value="<?= Str::e($starts) ?>">
      </div>
      <div>
        <label for="ends_at">Ends at</label>
        <input type="datetime-local" id="ends_at" name="ends_at" value="<?= Str::e($ends) ?>">
      </div>
      <div class="checkbox-row">
        <label><input type="checkbox" name="is_published" value="1" <?= !empty($item['is_published']) || $isNew ? 'checked' : '' ?>> Published</label>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn" type="submit"><?= $isNew ? 'Create' : 'Save' ?></button>
    </div>
  </form>
</div>
