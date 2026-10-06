<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$user = $mail->requireAdmin();
$activeFolder = 'admin';
$title = 'Mailbox users';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $op = (string) ($_POST['op'] ?? 'create');
    try {
        if ($op === 'create') {
            $mail->createUser(
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['display_name'] ?? ''),
                (string) ($_POST['password'] ?? ''),
                (string) ($_POST['role'] ?? 'user')
            );
            Adheri\Mail\MailSession::flash('success', 'Mailbox created.');
        } elseif ($op === 'update') {
            $mail->updateUser((int) ($_POST['user_id'] ?? 0), [
                'display_name' => (string) ($_POST['display_name'] ?? ''),
                'role' => (string) ($_POST['role'] ?? 'user'),
                'is_active' => !empty($_POST['is_active']),
                'password' => (string) ($_POST['password'] ?? ''),
            ]);
            Adheri\Mail\MailSession::flash('success', 'User updated.');
        }
        header('Location: ' . $mail->url('admin/users'));
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$users = $mail->listUsers();
require dirname(__DIR__) . '/partials/layout-start.php';
?>
<section class="mail-admin">
  <header class="mail-admin-head">
    <h1>Users</h1>
    <p class="mail-admin-lead">Create mailboxes and manage roles for <?= mail_h($mail->domain()) ?>.</p>
  </header>

  <?php if ($error !== ''): ?>
    <div class="mail-flash mail-flash-error"><?= mail_h($error) ?></div>
  <?php endif; ?>

  <div class="mail-settings-block mail-admin-create">
    <h2>Create mailbox</h2>
    <form method="post" class="mail-admin-create-form">
      <?= csrf_field() ?>
      <input type="hidden" name="op" value="create">
      <div class="mail-field">
        <label for="create-email">Email</label>
        <input id="create-email" type="email" name="email" required autocomplete="off" placeholder="name@<?= mail_h($mail->domain()) ?>">
      </div>
      <div class="mail-field">
        <label for="create-name">Display name</label>
        <input id="create-name" name="display_name" required autocomplete="name">
      </div>
      <div class="mail-field">
        <label for="create-password">Temporary password</label>
        <input id="create-password" type="text" name="password" required minlength="12" autocomplete="new-password">
      </div>
      <div class="mail-field">
        <label for="create-role">Role</label>
        <select id="create-role" name="role" data-lb-select>
          <option value="user">User</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div class="mail-admin-create-actions">
        <button type="submit" class="btn-mail-primary">Create mailbox</button>
      </div>
    </form>
  </div>

  <div class="mail-settings-block mail-admin-existing">
    <div class="mail-admin-existing-head">
      <h2>Mailboxes</h2>
      <span class="mail-muted"><?= count($users) ?> total</span>
    </div>

    <?php if ($users === []): ?>
      <p class="mail-muted">No mailboxes yet. Create one above.</p>
    <?php else: ?>
      <div class="mail-user-board" role="table" aria-label="Mailbox users">
        <div class="mail-user-board-head" role="row">
          <span role="columnheader">Email</span>
          <span role="columnheader">Name</span>
          <span role="columnheader">Role</span>
          <span role="columnheader">Status</span>
          <span role="columnheader">Password</span>
          <span role="columnheader"><span class="visually-hidden">Actions</span></span>
        </div>
        <?php foreach ($users as $u): ?>
          <form method="post" class="mail-user-row" role="row">
            <?= csrf_field() ?>
            <input type="hidden" name="op" value="update">
            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">

            <div class="mail-user-email" role="cell">
              <span class="mail-user-email-text"><?= mail_h((string) $u['email']) ?></span>
            </div>

            <div class="mail-field mail-user-name" role="cell">
              <label class="visually-hidden" for="name-<?= (int) $u['id'] ?>">Display name</label>
              <input id="name-<?= (int) $u['id'] ?>" name="display_name" value="<?= mail_h((string) $u['display_name']) ?>" required>
            </div>

            <div class="mail-field mail-user-role" role="cell">
              <label class="visually-hidden" for="role-<?= (int) $u['id'] ?>">Role</label>
              <select id="role-<?= (int) $u['id'] ?>" name="role" data-lb-select>
                <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>User</option>
                <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
              </select>
            </div>

            <div class="mail-user-status" role="cell">
              <label class="mail-switch">
                <input type="checkbox" name="is_active" value="1" <?= (int) $u['is_active'] === 1 ? 'checked' : '' ?>>
                <span>Active</span>
              </label>
            </div>

            <div class="mail-field mail-user-password" role="cell">
              <label class="visually-hidden" for="pass-<?= (int) $u['id'] ?>">New password</label>
              <input id="pass-<?= (int) $u['id'] ?>" type="password" name="password" placeholder="Optional" minlength="12" autocomplete="new-password">
            </div>

            <div class="mail-user-actions" role="cell">
              <button type="submit" class="btn-mail-primary mail-user-save">Save</button>
            </div>
          </form>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require dirname(__DIR__) . '/partials/layout-end.php'; ?>
