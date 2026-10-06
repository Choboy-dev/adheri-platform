<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = $mail->requireUser();
$id = (int) ($_GET['id'] ?? 0);
$msg = $mail->getMessage($user, $id);
if ($msg === null || $msg['folder'] === 'drafts' || $msg['folder'] === 'scheduled') {
    http_response_code(404);
    echo 'Message not found';
    exit;
}

$mail->markRead($user, $id, true);
$attachments = $mail->getAttachments($id);
$to = $mail->decodeJsonList($msg['to_emails'] ?? '[]');
$cc = $mail->decodeJsonList($msg['cc_emails'] ?? '[]');
$recipients = array_merge($to, $cc);
$avatar = $mail->avatarUrlForEmail((string) $msg['from_email']);
$activeFolder = (string) $msg['folder'];
$backFolder = in_array($activeFolder, ['inbox', 'starred', 'sent', 'archive', 'spam', 'trash'], true)
    ? $activeFolder
    : 'inbox';
$backHref = mail_path() . '?folder=' . rawurlencode($backFolder);
$backLabel = match ($backFolder) {
    'starred' => 'Starred',
    'sent' => 'Sent',
    'archive' => 'Archive',
    'spam' => 'Spam',
    'trash' => 'Trash',
    default => 'Inbox',
};
$title = (string) ($msg['subject'] !== '' ? $msg['subject'] : '(no subject)');
$srcdoc = $mail->htmlSandboxSrcdoc((string) ($msg['body_html'] ?? ''));

require __DIR__ . '/partials/layout-start.php';
?>
<article class="mail-message">
  <div class="mail-message-toolbar">
    <a class="mail-back-btn" href="<?= mail_h($backHref) ?>">
      <?= mail_icon('back') ?>
      <span>Back to <?= mail_h($backLabel) ?></span>
    </a>
    <div class="mail-message-actions">
      <a class="btn-mail-ghost" href="<?= mail_h(mail_path() . '?compose=reply&source=' . $id) ?>">Reply</a>
      <a class="btn-mail-ghost" href="<?= mail_h(mail_path() . '?compose=reply-all&source=' . $id) ?>">Reply all</a>
      <a class="btn-mail-ghost" href="<?= mail_h(mail_path() . '?compose=forward&source=' . $id) ?>">Forward</a>
    </div>
  </div>
  <header class="mail-message-head">
    <h1><?= mail_h($title) ?></h1>
  </header>
  <div class="mail-message-meta">
    <div class="mail-message-avatar">
      <?php if ($avatar): ?>
        <img src="<?= mail_h($avatar) ?>" alt="">
      <?php else: ?>
        <span><?= mail_h(mb_strtoupper(mb_substr((string) ($msg['from_name'] ?: $msg['from_email']), 0, 1))) ?></span>
      <?php endif; ?>
    </div>
    <div>
      <?php
      $fromEmail = (string) ($msg['from_email'] ?? '');
      $fromLabel = trim((string) ($msg['from_name'] ?? ''));
      if ($fromLabel === '' || strcasecmp($fromLabel, $fromEmail) === 0) {
          $fromLabel = '';
      }
      ?>
      <div class="mail-message-from">
        <?php if ($fromLabel !== ''): ?>
          <strong><?= mail_h($fromLabel) ?></strong>
          <span>&lt;<?= mail_h($fromEmail) ?>&gt;</span>
        <?php else: ?>
          <strong><?= mail_h($fromEmail) ?></strong>
        <?php endif; ?>
      </div>
      <div class="mail-message-to" data-mail-recipients-line title="<?= mail_h(implode(', ', $recipients)) ?>">
        to
        <?php
        $visibleRecipients = array_slice($recipients, 0, 2);
        $moreRecipients = max(0, count($recipients) - count($visibleRecipients));
        foreach ($visibleRecipients as $i => $r):
            ?><?= $i > 0 ? ', ' : '' ?><?= mail_h((string) $r) ?><?php
        endforeach;
        ?>
        <?php if ($moreRecipients > 0): ?>
          <button type="button" class="mail-more-recipients" data-mail-more-recipients>
            +<?= (int) $moreRecipients ?> more
          </button>
          <span data-mail-extra-recipients hidden>
            <?php foreach (array_slice($recipients, count($visibleRecipients)) as $r): ?>
              , <?= mail_h((string) $r) ?>
            <?php endforeach; ?>
          </span>
        <?php endif; ?>
      </div>
      <time><?= mail_h($mail->formatListDate((string) ($msg['received_at'] ?? $msg['sent_at'] ?? $msg['created_at'] ?? ''))) ?></time>
    </div>
  </div>

  <?php if ($attachments !== []): ?>
    <div class="mail-attach-chips">
      <?php foreach ($attachments as $att): ?>
        <a class="mail-attach-chip" href="<?= mail_h(mail_path('download') . '?id=' . (int) $att['id']) ?>">
          <?= mail_h((string) $att['filename']) ?>
          <small><?= number_format(((int) $att['size_bytes']) / 1024, 1) ?> KB</small>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (trim((string) ($msg['body_html'] ?? '')) !== ''): ?>
    <iframe class="mail-body-frame" sandbox="allow-same-origin allow-popups" srcdoc="<?= mail_h($srcdoc) ?>" title="Message body"></iframe>
  <?php else: ?>
    <pre class="mail-body-text"><?= mail_h((string) ($msg['body_text'] ?? '')) ?></pre>
  <?php endif; ?>
</article>
<?php require __DIR__ . '/partials/layout-end.php'; ?>
