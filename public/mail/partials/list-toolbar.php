<?php

declare(strict_types=1);

/** @var string $folder */
/** @var string|null $category */
/** @var array<string, int>|null $categoryCounts */
?>
<form method="post" action="<?= mail_h(mail_path('actions')) ?>" class="mail-list-toolbar" data-mail-toolbar-form>
  <?= csrf_field() ?>
  <input type="hidden" name="return_to" value="<?= mail_h($_SERVER['REQUEST_URI'] ?? mail_path()) ?>">
  <label class="mail-check-all"><input type="checkbox" data-mail-select-all aria-label="Select all"></label>
  <button type="submit" name="action" value="archive" title="Archive">Archive</button>
  <button type="submit" name="action" value="trash" title="Trash">Trash</button>
  <?php if (($folder ?? '') === 'trash'): ?>
    <button type="submit" name="action" value="delete" title="Delete forever" class="mail-danger">Delete forever</button>
  <?php endif; ?>
  <button type="submit" name="action" value="mark_read">Mark read</button>
  <button type="submit" name="action" value="mark_unread">Mark unread</button>
  <button type="submit" name="action" value="spam">Spam</button>
  <button type="submit" name="action" value="not_spam">Not spam</button>
</form>

<?php if (($folder ?? '') === 'inbox' && empty($_GET['q']) && isset($categoryCounts)): ?>
  <div class="mail-category-tabs" role="tablist">
    <?php foreach (['primary' => 'Primary', 'promotions' => 'Promotions', 'social' => 'Social', 'updates' => 'Updates'] as $key => $label): ?>
      <?php
        $active = ($category ?? 'primary') === $key;
        $href = mail_path() . '?folder=inbox&category=' . urlencode($key);
      ?>
      <a class="mail-cat-tab<?= $active ? ' is-active' : '' ?>" href="<?= mail_h($href) ?>" role="tab">
        <?= mail_h($label) ?>
        <?php if (!empty($categoryCounts[$key])): ?>
          <span><?= (int) $categoryCounts[$key] ?></span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
