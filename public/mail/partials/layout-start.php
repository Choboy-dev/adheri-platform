<?php

declare(strict_types=1);

/** @var array<string, mixed> $user */
/** @var Adheri\Mail\MailService $mail */
/** @var string $activeFolder */
/** @var string $title */

$counts = $mail->folderCounts($user);
$inboxState = $mail->inboxState($user);
$nav = mail_nav_items($user, $counts);
$activeFolder = $activeFolder ?? 'inbox';
$flash = Adheri\Mail\MailSession::pullFlash();
$avatarUrl = $mail->avatarUrl($user);
$initials = $mail->userInitials($user);
$logo = mail_asset('img/ADHERI-new-logo-1.png');
require __DIR__ . '/head-meta.php';
?>
<body
  class="mail-body"
  data-mail-path="<?= mail_h($mail->path()) ?>"
  data-mail-latest-id="<?= (int) $inboxState['latest_id'] ?>"
  data-mail-unread="<?= (int) $inboxState['unread'] ?>"
>
<div class="mail-shell">
  <aside class="mail-sidebar" data-mail-sidebar>
    <a class="mail-logo" href="<?= mail_h(mail_path()) ?>">
      <img src="<?= mail_h($logo) ?>" alt="ADHERI" width="140" height="36">
      <span class="mail-brand-word">Mail</span>
    </a>
    <a class="mail-compose-btn" href="<?= mail_h(mail_path() . '?compose=new') ?>"><?= mail_icon('compose') ?><span>Compose</span></a>
    <nav class="mail-nav" aria-label="Folders">
      <?php foreach ($nav as $item): ?>
        <a class="mail-nav-item<?= ($activeFolder === $item['key']) ? ' is-active' : '' ?>" href="<?= mail_h($item['href']) ?>">
          <?= mail_icon($item['icon']) ?>
          <span class="mail-nav-label"><?= mail_h($item['label']) ?></span>
          <?php if (!empty($item['badge'])): ?>
            <span class="mail-nav-badge"><?= (int) $item['badge'] ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
      <a class="mail-nav-item<?= ($activeFolder === 'settings') ? ' is-active' : '' ?>" href="<?= mail_h(mail_path('settings')) ?>">
        <?= mail_icon('settings') ?><span class="mail-nav-label">Settings</span>
      </a>
      <?php if (($user['role'] ?? '') === 'admin'): ?>
        <a class="mail-nav-item<?= ($activeFolder === 'admin') ? ' is-active' : '' ?>" href="<?= mail_h(mail_path('admin/users')) ?>">
          <?= mail_icon('users') ?><span class="mail-nav-label">Users</span>
        </a>
      <?php endif; ?>
    </nav>
  </aside>

  <div class="mail-main">
    <header class="mail-topbar">
      <button type="button" class="mail-icon-btn" data-mail-sidebar-toggle aria-label="Menu"><?= mail_icon('menu') ?></button>
      <form class="mail-search" method="get" action="<?= mail_h(mail_path()) ?>">
        <input type="hidden" name="folder" value="<?= mail_h($activeFolder === 'starred' ? 'starred' : ($activeFolder ?: 'inbox')) ?>">
        <span class="mail-search-icon" aria-hidden="true"><?= mail_icon('search') ?></span>
        <input type="search" name="q" value="<?= mail_h($_GET['q'] ?? '') ?>" placeholder="Search mail" aria-label="Search mail">
      </form>
      <button type="button" class="mail-icon-btn" data-mail-sound-toggle aria-label="Toggle notification sound" title="Sound"><?= mail_icon('bell') ?></button>
      <div class="mail-account" data-mail-account>
        <button
          type="button"
          class="mail-avatar-btn"
          data-mail-account-toggle
          aria-haspopup="menu"
          aria-expanded="false"
          aria-controls="mail-account-menu"
          title="<?= mail_h((string) $user['email']) ?>"
          aria-label="Account menu for <?= mail_h((string) $user['display_name'] ?: (string) $user['email']) ?>"
        >
          <?php if ($avatarUrl): ?>
            <img src="<?= mail_h($avatarUrl) ?>" alt="">
          <?php else: ?>
            <span style="background:<?= mail_h((string) ($user['avatar_color'] ?? '#0071BC')) ?>"><?= mail_h($initials) ?></span>
          <?php endif; ?>
        </button>
        <div class="mail-account-menu" id="mail-account-menu" data-mail-account-menu role="menu" hidden>
          <div class="mail-account-head">
            <p class="mail-account-name"><?= mail_h((string) ($user['display_name'] ?? 'Mailbox')) ?></p>
            <p class="mail-account-email"><?= mail_h((string) $user['email']) ?></p>
          </div>
          <a class="mail-account-item" role="menuitem" href="<?= mail_h(mail_path('settings')) ?>">Settings</a>
          <form method="post" action="<?= mail_h(mail_path('logout')) ?>" class="mail-account-logout">
            <?= csrf_field() ?>
            <button type="submit" class="mail-account-item mail-account-signout" role="menuitem">Sign out</button>
          </form>
        </div>
      </div>
    </header>

    <?php if ($flash): ?>
      <div class="mail-flash mail-flash-<?= mail_h($flash['type']) ?>"><?= mail_h($flash['message']) ?></div>
    <?php endif; ?>
    <div class="mail-live-toast" data-mail-toast hidden>New mail arrived</div>
