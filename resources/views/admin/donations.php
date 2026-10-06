<?php

use Adheri\Support\Str;

/** @var list<array<string, mixed>> $items */
?>
<div class="panel">
  <table>
    <thead><tr><th>Donor</th><th>Email</th><th>Amount</th><th>Status</th><th>Reference</th><th>Date</th></tr></thead>
    <tbody>
      <?php if ($items === []): ?><tr><td colspan="6" class="muted">No donations yet.</td></tr><?php endif; ?>
      <?php foreach ($items as $row): ?>
        <tr>
          <td><?= Str::e((string) $row['donor_name']) ?></td>
          <td><?= Str::e((string) $row['donor_email']) ?></td>
          <td>₦<?= number_format((float) $row['amount'], 2) ?></td>
          <td><?= Str::e((string) $row['status']) ?></td>
          <td><?= Str::e((string) $row['flutterwave_tx_ref']) ?></td>
          <td><?= Str::e((string) $row['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
