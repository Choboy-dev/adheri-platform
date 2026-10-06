<?php

use Adheri\Security\Csrf;
use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var list<array<string, mixed>> $items */
?>
<p class="form-actions" style="margin-bottom:1rem">
  <a class="btn" href="<?= Str::e(Url::to('admin/announcements/new')) ?>">New announcement</a>
</p>

<div class="panel">
  <table>
    <thead><tr><th>Preview</th><th>Title</th><th>Published</th><th>Order</th><th></th></tr></thead>
    <tbody>
      <?php if ($items === []): ?><tr><td colspan="5" class="muted">No announcement flyers yet.</td></tr><?php endif; ?>
      <?php foreach ($items as $row): ?>
        <tr>
          <td>
            <?php if (!empty($row['image_url'])): ?>
              <img class="thumb-sm" src="<?= Str::e(Url::to(ltrim((string) $row['image_url'], '/'))) ?>" alt="">
            <?php endif; ?>
          </td>
          <td><?= Str::e((string) ($row['title'] ?? 'Flyer #' . $row['id'])) ?></td>
          <td><?= !empty($row['is_published']) ? 'Yes' : 'No' ?></td>
          <td><?= (int) $row['sort_order'] ?></td>
          <td>
            <a href="<?= Str::e(Url::to('admin/announcements/' . (int) $row['id'] . '/edit')) ?>">Edit</a>
            <form method="post" action="<?= Str::e(Url::to('admin/announcements/' . (int) $row['id'] . '/delete')) ?>" style="display:inline" onsubmit="return confirm('Delete this announcement?');">
              <?= Csrf::field() ?>
              <button class="btn btn-ghost btn-sm" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
