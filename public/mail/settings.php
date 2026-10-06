<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = $mail->requireUser();
$activeFolder = 'settings';
$title = 'Settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $op = (string) ($_POST['op'] ?? '');
    try {
        if ($op === 'prefs') {
            $mail->updateSignaturePreferences(
                $user,
                !empty($_POST['signature_on_new']),
                !empty($_POST['signature_on_reply'])
            );
            Adheri\Mail\MailSession::flash('success', 'Preferences saved.');
        } elseif ($op === 'signature_save') {
            $id = ($_POST['signature_id'] ?? '') !== '' ? (int) $_POST['signature_id'] : null;
            $mail->saveSignature(
                $user,
                $id,
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['body_html'] ?? ''),
                !empty($_POST['is_default'])
            );
            Adheri\Mail\MailSession::flash('success', 'Signature saved.');
        } elseif ($op === 'signature_delete') {
            $mail->deleteSignature($user, (int) ($_POST['signature_id'] ?? 0));
            Adheri\Mail\MailSession::flash('success', 'Signature deleted.');
        } elseif ($op === 'avatar') {
            $res = $mail->storeAvatar($user, $_FILES['avatar'] ?? []);
            if (!$res['ok']) {
                throw new RuntimeException($res['error'] ?? 'Upload failed');
            }
            Adheri\Mail\MailSession::flash('success', 'Photo updated.');
        } elseif ($op === 'avatar_remove') {
            $mail->removeAvatar($user);
            Adheri\Mail\MailSession::flash('success', 'Photo removed.');
        } elseif ($op === 'recovery_email') {
            $mail->updateRecoveryEmail($user, (string) ($_POST['recovery_email'] ?? ''));
            Adheri\Mail\MailSession::flash('success', 'Recovery email saved.');
        }
        $user = $mail->findUserById((int) $user['id']) ?? $user;
    } catch (Throwable $e) {
        Adheri\Mail\MailSession::flash('error', $e->getMessage());
    }
    header('Location: ' . $mail->url('settings'));
    exit;
}

$signatures = $mail->listSignatures($user);
$editId = isset($_GET['signature']) ? (int) $_GET['signature'] : 0;
$editing = $editId ? $mail->getSignature($user, $editId) : null;
$avatarUrl = $mail->avatarUrl($user);

require __DIR__ . '/partials/layout-start.php';
?>
<section class="mail-settings">
  <header class="mail-settings-head">
    <h1>Settings</h1>
    <p class="mail-admin-lead">Photo, recovery email, signatures, and mailbox preferences.</p>
  </header>

  <div class="mail-settings-block" id="recovery">
    <h2>Recovery email</h2>
    <p class="mail-admin-lead">Personal address used for password reset codes. Must be different from <?= mail_h((string) $user['email']) ?>.</p>
    <form method="post" class="mail-prefs-form">
      <?= csrf_field() ?>
      <input type="hidden" name="op" value="recovery_email">
      <div class="mail-field">
        <label for="recovery-email">Personal recovery email</label>
        <input id="recovery-email" type="email" name="recovery_email" required autocomplete="email"
               placeholder="you@gmail.com"
               value="<?= mail_h((string) ($user['recovery_email'] ?? '')) ?>">
      </div>
      <div class="mail-settings-actions">
        <button type="submit" class="btn-mail-primary">Save recovery email</button>
        <a class="btn-mail-ghost" href="<?= mail_h($mail->url('change-password')) ?>">Change password</a>
      </div>
    </form>
  </div>

  <div class="mail-settings-block">
    <h2>Profile picture</h2>
    <div class="mail-avatar-settings">
      <div class="mail-avatar-preview" aria-hidden="true">
        <?php if ($avatarUrl): ?>
          <img src="<?= mail_h($avatarUrl) ?>" alt="">
        <?php else: ?>
          <span class="mail-avatar-fallback"><?= mail_h($mail->userInitials($user)) ?></span>
        <?php endif; ?>
      </div>
      <div class="mail-avatar-controls">
        <form method="post" enctype="multipart/form-data" class="mail-avatar-upload" data-mail-avatar-form>
          <?= csrf_field() ?>
          <input type="hidden" name="op" value="avatar">
          <input id="settings-avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" required hidden data-mail-avatar-input>
          <div class="mail-file-pick">
            <label for="settings-avatar" class="btn-mail-ghost mail-file-btn">Choose photo</label>
            <span class="mail-file-name mail-muted" data-mail-avatar-name>No file chosen</span>
          </div>
          <div class="mail-settings-actions">
            <button type="submit" class="btn-mail-primary">Upload</button>
          </div>
        </form>
        <?php if ($avatarUrl): ?>
          <form method="post" class="mail-avatar-remove">
            <?= csrf_field() ?>
            <input type="hidden" name="op" value="avatar_remove">
            <button type="submit" class="btn-mail-ghost">Remove</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="mail-settings-block">
    <h2>Signature preferences</h2>
    <form method="post" class="mail-prefs-form">
      <?= csrf_field() ?>
      <input type="hidden" name="op" value="prefs">
      <label class="mail-check">
        <input type="checkbox" name="signature_on_new" value="1" <?= (int) $user['signature_on_new'] === 1 ? 'checked' : '' ?>>
        <span>Append on new messages</span>
      </label>
      <label class="mail-check">
        <input type="checkbox" name="signature_on_reply" value="1" <?= (int) $user['signature_on_reply'] === 1 ? 'checked' : '' ?>>
        <span>Append on reply / forward</span>
      </label>
      <div class="mail-settings-actions">
        <button type="submit" class="btn-mail-primary">Save preferences</button>
      </div>
    </form>
  </div>

  <div class="mail-settings-block">
    <div class="mail-admin-existing-head">
      <h2>Signatures</h2>
      <span class="mail-muted"><?= count($signatures) ?> saved</span>
    </div>

    <?php if ($signatures !== []): ?>
      <ul class="mail-sig-list">
        <?php foreach ($signatures as $sig): ?>
          <li class="mail-sig-item">
            <div class="mail-sig-meta">
              <strong><?= mail_h((string) $sig['name']) ?></strong>
              <?php if ((int) $sig['is_default'] === 1): ?>
                <span class="mail-sig-badge">Default</span>
              <?php endif; ?>
            </div>
            <div class="mail-sig-item-actions">
              <a class="btn-mail-ghost" href="<?= mail_h(mail_path('settings') . '?signature=' . (int) $sig['id']) ?>">Edit</a>
              <form method="post" data-mail-sig-delete>
                <?= csrf_field() ?>
                <input type="hidden" name="op" value="signature_delete">
                <input type="hidden" name="signature_id" value="<?= (int) $sig['id'] ?>">
                <button type="submit" class="mail-danger">Delete</button>
              </form>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="mail-muted mail-sig-empty">No signatures yet. Create one below.</p>
    <?php endif; ?>

    <h3 class="mail-sig-form-title"><?= $editing ? 'Edit signature' : 'New signature' ?></h3>
    <form method="post" class="mail-sig-form" data-mail-sig-form>
      <?= csrf_field() ?>
      <input type="hidden" name="op" value="signature_save">
      <input type="hidden" name="signature_id" value="<?= $editing ? (int) $editing['id'] : '' ?>">
      <div class="mail-field">
        <label for="sig-name">Name</label>
        <input id="sig-name" name="name" required maxlength="120" value="<?= mail_h((string) ($editing['name'] ?? '')) ?>">
      </div>
      <div class="mail-field">
        <label for="sig-body">Body</label>
        <textarea id="sig-body" name="body_html" rows="8" required><?= mail_h((string) ($editing['body_html'] ?? '')) ?></textarea>
      </div>
      <label class="mail-check">
        <input type="checkbox" name="is_default" value="1" <?= $editing && (int) $editing['is_default'] === 1 ? 'checked' : '' ?>>
        <span>Default signature</span>
      </label>
      <div class="mail-settings-actions">
        <button type="submit" class="btn-mail-primary">Save signature</button>
        <?php if ($editing): ?>
          <a class="btn-mail-ghost" href="<?= mail_h(mail_path('settings')) ?>">Cancel</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</section>
<?php require __DIR__ . '/partials/layout-end.php'; ?>
