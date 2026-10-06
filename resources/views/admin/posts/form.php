<?php

use Adheri\Security\Csrf;
use Adheri\Support\Str;
use Adheri\Support\Url;
use Adheri\View;

/** @var array<string, mixed>|null $item */
/** @var list<array<string, mixed>> $categories */
/** @var string|null $featuredImageUrl */
$item = is_array($item ?? null) ? $item : null;
$isEdit = $item !== null;
$action = $isEdit ? Url::to('admin/posts/edit/' . (int) $item['id']) : Url::to('admin/posts/create');
$body = (string) ($item['body'] ?? '');
$featuredImageUrl = $featuredImageUrl ?? null;
$hasFeatured = is_string($featuredImageUrl) && $featuredImageUrl !== '';
?>
<link rel="stylesheet" href="<?= Str::e(Url::asset('css/docs-editor.css')) ?>">

<form
  class="post-editor-form"
  method="post"
  action="<?= Str::e($action) ?>"
  enctype="multipart/form-data"
  data-docs-form
>
  <?= Csrf::field() ?>

  <div class="docs-chrome">
    <div class="docs-menubar">
      <div class="docs-menubar-left">
        <a class="docs-back" href="<?= Str::e(Url::to('admin/posts')) ?>" aria-label="Back to posts">←</a>
        <input
          class="docs-title-input"
          type="text"
          name="title"
          required
          placeholder="Untitled document"
          value="<?= Str::e((string) ($item['title'] ?? '')) ?>"
          autocomplete="off"
        >
      </div>
      <div class="docs-menubar-right">
        <button class="btn btn-ghost docs-save-draft" type="submit" name="status" value="draft">Save as draft</button>
        <button class="btn docs-publish" type="submit" name="status" value="published">Publish</button>
      </div>
    </div>

    <div class="docs-toolbar" role="toolbar" aria-label="Formatting">
      <div class="docs-toolbar-group">
        <button type="button" class="docs-tool" data-command="undo" title="Undo" aria-label="Undo"><?= View::render('admin/partials/docs-icons', ['icon' => 'undo']) ?></button>
        <button type="button" class="docs-tool" data-command="redo" title="Redo" aria-label="Redo"><?= View::render('admin/partials/docs-icons', ['icon' => 'redo']) ?></button>
      </div>
      <div class="docs-toolbar-divider" aria-hidden="true"></div>
      <div class="docs-toolbar-group">
        <select class="docs-select" data-command="heading" title="Styles" aria-label="Text style">
          <option value="paragraph">Normal text</option>
          <option value="1">Heading 1</option>
          <option value="2">Heading 2</option>
          <option value="3">Heading 3</option>
        </select>
      </div>
      <div class="docs-toolbar-divider" aria-hidden="true"></div>
      <div class="docs-toolbar-group">
        <button type="button" class="docs-tool" data-command="bold" title="Bold (⌘B)" aria-label="Bold"><strong>B</strong></button>
        <button type="button" class="docs-tool" data-command="italic" title="Italic (⌘I)" aria-label="Italic"><em>I</em></button>
        <button type="button" class="docs-tool" data-command="underline" title="Underline (⌘U)" aria-label="Underline"><span style="text-decoration:underline">U</span></button>
        <button type="button" class="docs-tool" data-command="strike" title="Strikethrough" aria-label="Strikethrough"><span style="text-decoration:line-through">S</span></button>
      </div>
      <div class="docs-toolbar-divider" aria-hidden="true"></div>
      <div class="docs-toolbar-group">
        <button type="button" class="docs-tool" data-command="bulletList" title="Bulleted list" aria-label="Bulleted list"><?= View::render('admin/partials/docs-icons', ['icon' => 'bullet']) ?></button>
        <button type="button" class="docs-tool" data-command="orderedList" title="Numbered list" aria-label="Numbered list"><?= View::render('admin/partials/docs-icons', ['icon' => 'ordered']) ?></button>
      </div>
      <div class="docs-toolbar-divider" aria-hidden="true"></div>
      <div class="docs-toolbar-group">
        <button type="button" class="docs-tool" data-command="alignLeft" title="Align left" aria-label="Align left"><?= View::render('admin/partials/docs-icons', ['icon' => 'alignLeft']) ?></button>
        <button type="button" class="docs-tool" data-command="alignCenter" title="Align center" aria-label="Align center"><?= View::render('admin/partials/docs-icons', ['icon' => 'alignCenter']) ?></button>
        <button type="button" class="docs-tool" data-command="alignRight" title="Align right" aria-label="Align right"><?= View::render('admin/partials/docs-icons', ['icon' => 'alignRight']) ?></button>
      </div>
      <div class="docs-toolbar-divider" aria-hidden="true"></div>
      <div class="docs-toolbar-group">
        <button type="button" class="docs-tool" data-command="link" title="Insert link" aria-label="Insert link"><?= View::render('admin/partials/docs-icons', ['icon' => 'link']) ?></button>
        <button type="button" class="docs-tool" data-command="image" title="Insert image" aria-label="Insert image"><?= View::render('admin/partials/docs-icons', ['icon' => 'image']) ?></button>
        <div class="docs-table-insert" data-table-insert>
          <button type="button" class="docs-tool" data-command="tablePicker" title="Insert table" aria-label="Insert table" aria-expanded="false" aria-haspopup="true"><?= View::render('admin/partials/docs-icons', ['icon' => 'table']) ?></button>
          <div class="docs-table-picker" data-table-picker hidden>
            <p class="docs-table-picker-label" data-table-picker-label>Insert table</p>
            <div class="docs-table-grid" data-table-grid role="grid" aria-label="Table size"></div>
          </div>
        </div>
        <button type="button" class="docs-tool" data-command="blockquote" title="Quote" aria-label="Quote"><?= View::render('admin/partials/docs-icons', ['icon' => 'quote']) ?></button>
        <button type="button" class="docs-tool" data-command="clear" title="Clear formatting" aria-label="Clear formatting"><?= View::render('admin/partials/docs-icons', ['icon' => 'clear']) ?></button>
      </div>
    </div>

    <div class="docs-table-bar" data-table-bar hidden>
      <span class="docs-table-bar-label">Table</span>
      <button type="button" class="docs-tool" data-command="addRowBefore" title="Insert row above" aria-label="Insert row above"><?= View::render('admin/partials/docs-icons', ['icon' => 'rowAbove']) ?></button>
      <button type="button" class="docs-tool" data-command="addRowAfter" title="Insert row below" aria-label="Insert row below"><?= View::render('admin/partials/docs-icons', ['icon' => 'rowBelow']) ?></button>
      <button type="button" class="docs-tool" data-command="addColumnBefore" title="Insert column left" aria-label="Insert column left"><?= View::render('admin/partials/docs-icons', ['icon' => 'colLeft']) ?></button>
      <button type="button" class="docs-tool" data-command="addColumnAfter" title="Insert column right" aria-label="Insert column right"><?= View::render('admin/partials/docs-icons', ['icon' => 'colRight']) ?></button>
      <span class="docs-toolbar-divider" aria-hidden="true"></span>
      <button type="button" class="docs-tool" data-command="deleteRow" title="Delete row" aria-label="Delete row"><?= View::render('admin/partials/docs-icons', ['icon' => 'deleteRow']) ?></button>
      <button type="button" class="docs-tool" data-command="deleteColumn" title="Delete column" aria-label="Delete column"><?= View::render('admin/partials/docs-icons', ['icon' => 'deleteCol']) ?></button>
      <button type="button" class="docs-tool" data-command="mergeCells" title="Merge cells" aria-label="Merge cells"><?= View::render('admin/partials/docs-icons', ['icon' => 'merge']) ?></button>
      <button type="button" class="docs-tool" data-command="splitCell" title="Split cell" aria-label="Split cell"><?= View::render('admin/partials/docs-icons', ['icon' => 'split']) ?></button>
      <button type="button" class="docs-tool" data-command="toggleHeaderRow" title="Toggle header row" aria-label="Toggle header row"><?= View::render('admin/partials/docs-icons', ['icon' => 'headerRow']) ?></button>
      <span class="docs-toolbar-divider" aria-hidden="true"></span>
      <button type="button" class="docs-tool docs-tool-danger" data-command="deleteTable" title="Delete table" aria-label="Delete table"><?= View::render('admin/partials/docs-icons', ['icon' => 'deleteTable']) ?></button>
    </div>

    <div class="docs-workspace">
      <div class="docs-page">
        <div id="docs-editor" class="docs-editor" data-docs-editor></div>
        <textarea name="body" id="docs-body" hidden></textarea>
        <script type="application/json" id="docs-initial-content"><?= json_encode($body, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
      </div>

      <aside class="docs-sidebar panel">
        <h2 style="margin-top:0;font-size:1rem">Document details</h2>
        <label for="slug">Slug</label>
        <input id="slug" name="slug" value="<?= Str::e((string) ($item['slug'] ?? '')) ?>" placeholder="auto-from-title">

        <label for="excerpt">Excerpt</label>
        <textarea id="excerpt" name="excerpt" rows="3"><?= Str::e((string) ($item['excerpt'] ?? '')) ?></textarea>

        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
          <option value="">— None —</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['id'] ?>" <?= ((int) ($item['category_id'] ?? 0) === (int) $cat['id']) ? 'selected' : '' ?>>
              <?= Str::e((string) $cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="published_at">Publish date</label>
        <input id="published_at" type="datetime-local" name="published_at" value="<?= Str::e(!empty($item['published_at']) ? date('Y-m-d\TH:i', strtotime((string) $item['published_at'])) : '') ?>">

        <div class="featured-image-field" data-featured-image>
          <div class="featured-image-label">Featured image</div>
          <div class="featured-image-box<?= $hasFeatured ? ' has-preview' : '' ?>">
            <div class="featured-image-preview" data-featured-preview <?= $hasFeatured ? '' : 'hidden' ?>>
              <?php if ($hasFeatured): ?>
                <img src="<?= Str::e($featuredImageUrl) ?>" alt="" data-featured-img>
              <?php else: ?>
                <img alt="" data-featured-img hidden>
              <?php endif; ?>
            </div>
            <label class="featured-image-drop" for="cover" data-featured-empty <?= $hasFeatured ? 'hidden' : '' ?>>
              <span class="featured-image-icon" aria-hidden="true"><?= View::render('admin/partials/docs-icons', ['icon' => 'featured']) ?></span>
              <span class="featured-image-cta">Set featured image</span>
              <span class="featured-image-hint">JPEG, PNG, WebP, or GIF · max 8 MB</span>
            </label>
            <input id="cover" class="featured-image-input" type="file" name="cover" accept="image/jpeg,image/png,image/webp,image/gif" data-featured-input>
          </div>
          <div class="featured-image-actions" data-featured-actions <?= $hasFeatured ? '' : 'hidden' ?>>
            <label class="featured-image-replace" for="cover">Replace image</label>
            <button type="button" class="featured-image-remove" data-featured-remove>Remove featured image</button>
          </div>
          <input type="hidden" name="remove_cover" value="0" data-featured-remove-flag>
        </div>

        <label for="seo_title">SEO title</label>
        <input id="seo_title" name="seo_title" value="<?= Str::e((string) ($item['seo_title'] ?? '')) ?>">

        <label for="seo_description">SEO description</label>
        <textarea id="seo_description" name="seo_description" rows="3"><?= Str::e((string) ($item['seo_description'] ?? '')) ?></textarea>
      </aside>
    </div>
  </div>
</form>

<input type="file" id="docs-image-input" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
<script>
  window.ADHERI_DOCS = {
    uploadUrl: <?= json_encode(Url::to('admin/posts/upload-image'), JSON_THROW_ON_ERROR) ?>,
    csrfToken: <?= json_encode(Csrf::token(), JSON_THROW_ON_ERROR) ?>
  };
</script>
<script type="module" src="<?= Str::e(Url::asset('js/docs-editor.js')) ?>"></script>
