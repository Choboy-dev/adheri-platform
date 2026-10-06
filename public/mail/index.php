<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = $mail->requireUser();
$folder = (string) ($_GET['folder'] ?? 'inbox');
$category = isset($_GET['category']) ? (string) $_GET['category'] : 'primary';
$q = isset($_GET['q']) ? trim((string) $_GET['q']) : null;
$page = max(1, (int) ($_GET['page'] ?? 1));
if ($q === '') {
    $q = null;
}

$result = $mail->listMessages($user, $folder, $folder === 'inbox' ? $category : null, $q, $page);
$categoryCounts = $folder === 'inbox' && $q === null ? $mail->inboxCategoryCounts($user) : null;
$activeFolder = $folder;
$title = $mail->appName() . ' — ' . ucfirst($folder);

require __DIR__ . '/partials/layout-start.php';
require __DIR__ . '/partials/list-toolbar.php';
?>
<div class="mail-list" data-mail-list>
  <?php if ($result['items'] === []): ?>
    <div class="mail-empty">No messages.</div>
  <?php else: ?>
    <?php foreach ($result['items'] as $msg): ?>
      <?php
        $id = (int) $msg['id'];
        $unread = (int) $msg['is_read'] === 0;
        $starred = (int) $msg['is_starred'] === 1;
        $when = $mail->formatListDate((string) ($msg['scheduled_at'] ?? $msg['received_at'] ?? $msg['sent_at'] ?? $msg['created_at'] ?? ''));
        if ($folder === 'drafts') {
            $href = mail_path() . '?folder=drafts&compose=draft&draft=' . $id;
        } elseif ($folder === 'scheduled') {
            $href = '';
        } else {
            $href = mail_path('message') . '?id=' . $id;
        }
      ?>
      <div class="mail-row<?= $unread ? ' is-unread' : '' ?>" data-mail-row<?= $href !== '' ? ' data-href="' . mail_h($href) . '"' : '' ?>>
        <label class="mail-row-check" onclick="event.stopPropagation()">
          <input type="checkbox" form="" name="ids[]" value="<?= $id ?>" data-mail-row-check>
        </label>
        <?php if ($folder === 'scheduled'): ?>
          <button type="button" class="mail-star-btn" data-mail-cancel-schedule="<?= $id ?>" title="Cancel send" onclick="event.stopPropagation()">×</button>
        <?php else: ?>
          <button type="button" class="mail-star-btn<?= $starred ? ' is-on' : '' ?>" data-mail-star="<?= $id ?>" title="Star" onclick="event.stopPropagation()">★</button>
        <?php endif; ?>
        <div class="mail-row-sender"><?= mail_h($mail->displaySender($msg)) ?></div>
        <div class="mail-row-main">
          <span class="mail-row-subject"><?= mail_h((string) ($msg['subject'] !== '' ? $msg['subject'] : '(no subject)')) ?></span>
          <span class="mail-row-snippet"> — <?= mail_h((string) ($msg['snippet'] ?? '')) ?></span>
        </div>
        <time class="mail-row-time"><?= mail_h($when) ?></time>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php if ($result['pages'] > 1): ?>
  <nav class="mail-pager">
    <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
      <?php
        $qs = $_GET;
        $qs['page'] = $p;
        $href = mail_path() . '?' . http_build_query($qs);
      ?>
      <a class="<?= $p === $result['page'] ? 'is-active' : '' ?>" href="<?= mail_h($href) ?>"><?= $p ?></a>
    <?php endfor; ?>
  </nav>
<?php endif; ?>

<script>
// Wire checkboxes into toolbar form
document.querySelectorAll('[data-mail-row-check]').forEach((cb) => {
  cb.setAttribute('form', document.querySelector('[data-mail-toolbar-form]')?.getAttribute('id') || '');
});
</script>
<?php
// Give toolbar form an id for checkbox association
?>
<script>
(() => {
  const form = document.querySelector('[data-mail-toolbar-form]');
  if (!form) return;
  form.id = 'mail-toolbar-form';
  document.querySelectorAll('[data-mail-row-check]').forEach((cb) => { cb.setAttribute('form', 'mail-toolbar-form'); });
})();
</script>
<?php require __DIR__ . '/partials/layout-end.php'; ?>
