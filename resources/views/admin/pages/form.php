<?php

use Adheri\Security\Csrf;
use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var array<string, mixed> $page */
/** @var list<array<string, mixed>> $sections */
?>
<form method="post" action="<?= Str::e(Url::to('admin/pages/edit/' . (int) $page['id'])) ?>" class="admin-form">
  <?= Csrf::field() ?>
  <div class="panel" style="margin-bottom:1rem">
    <label for="title">Page title</label>
    <input id="title" name="title" required value="<?= Str::e((string) $page['title']) ?>">
    <label for="seo_title">SEO title</label>
    <input id="seo_title" name="seo_title" value="<?= Str::e((string) ($page['seo_title'] ?? '')) ?>">
    <label for="seo_description">SEO description</label>
    <textarea id="seo_description" name="seo_description" rows="3"><?= Str::e((string) ($page['seo_description'] ?? '')) ?></textarea>
  </div>

  <?php foreach ($sections as $section): ?>
    <div class="panel" style="margin-bottom:1rem">
      <h2 style="margin-top:0;font-size:1rem"><?= Str::e((string) $section['section_key']) ?></h2>
      <label>Section title</label>
      <input name="sections[<?= (int) $section['id'] ?>][title]" value="<?= Str::e((string) ($section['title'] ?? '')) ?>">
      <label>Body</label>
      <textarea name="sections[<?= (int) $section['id'] ?>][body]" rows="6"><?= Str::e((string) ($section['body'] ?? '')) ?></textarea>
      <label class="remember" style="margin-top:0.75rem">
        <input type="checkbox" name="sections[<?= (int) $section['id'] ?>][is_visible]" value="1" <?= !empty($section['is_visible']) ? 'checked' : '' ?>>
        <span>Visible</span>
      </label>
    </div>
  <?php endforeach; ?>

  <div class="form-actions">
    <button class="btn" type="submit">Save page</button>
    <a class="btn btn-ghost" href="<?= Str::e(Url::to('admin/pages')) ?>">Cancel</a>
  </div>
</form>
