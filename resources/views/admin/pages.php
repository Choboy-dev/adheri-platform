<?php

use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var list<array<string, mixed>> $items */
?>
<div class="panel">
  <table>
    <thead><tr><th>Title</th><th>Slug</th><th>Updated</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($items as $row): ?>
        <tr>
          <td><?= Str::e((string) $row['title']) ?></td>
          <td><?= Str::e((string) $row['slug']) ?></td>
          <td><?= Str::e((string) $row['updated_at']) ?></td>
          <td><a class="btn btn-ghost btn-sm" href="<?= Str::e(Url::to('admin/pages/edit/' . (int) $row['id'])) ?>">Edit</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
