<?php

use Adheri\Security\Csrf;
use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var list<array<string, mixed>> $items */

$statuses = ['new', 'read', 'archived'];
?>
<div class="panel">
  <?php if ($items === []): ?>
    <p class="muted">No inquiries yet.</p>
  <?php else: ?>
    <?php foreach ($items as $row): ?>
      <?php
        $name = (string) ($row['full_name'] ?: trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
        $current = (string) $row['status'];
      ?>
      <article class="inquiry-card">
        <header class="inquiry-card-head">
          <span class="badge"><?= Str::e((string) $row['type']) ?></span>
          <strong><?= Str::e($name) ?></strong>
          <span class="muted"><?= Str::e((string) $row['email']) ?></span>
          <?php if (!empty($row['phone'])): ?>
            <span class="muted"> · <?= Str::e((string) $row['phone']) ?></span>
          <?php endif; ?>
          <span class="muted"> · <?= Str::e((string) $row['created_at']) ?></span>
        </header>
        <div class="inquiry-message"><?= nl2br(Str::e((string) $row['message'])) ?></div>
        <?php if (!empty($row['location']) || !empty($row['availability'])): ?>
          <p class="muted" style="margin:0.5rem 0 0">
            <?php if (!empty($row['location'])): ?>Location: <?= Str::e((string) $row['location']) ?><?php endif; ?>
            <?php if (!empty($row['availability'])): ?> · Availability: <?= Str::e((string) $row['availability']) ?><?php endif; ?>
          </p>
        <?php endif; ?>
        <div class="inquiry-actions">
          <?php foreach ($statuses as $status): ?>
            <form method="post" action="<?= Str::e(Url::to('admin/inquiries/' . (int) $row['id'] . '/status')) ?>">
              <?= Csrf::field() ?>
              <input type="hidden" name="status" value="<?= Str::e($status) ?>">
              <button class="btn btn-sm <?= $current === $status ? '' : 'btn-ghost' ?>" type="submit" <?= $current === $status ? 'disabled' : '' ?>>
                Mark <?= Str::e($status) ?>
              </button>
            </form>
          <?php endforeach; ?>
        </div>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
