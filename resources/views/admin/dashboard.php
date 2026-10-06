<?php

use Adheri\Support\Str;
use Adheri\Support\Url;
?>
<div class="stats">
  <div class="stat"><strong><?= (int) $newInquiries ?></strong><span>New inquiries</span></div>
  <div class="stat"><strong><?= (int) $programCount ?></strong><span>Programs</span></div>
  <div class="stat"><strong><?= (int) $postCount ?></strong><span>Blog posts</span></div>
  <div class="stat"><strong><?= (int) $albumCount ?></strong><span>Albums</span></div>
</div>

<div class="panel" style="margin-bottom:1rem">
  <h2>Recent inquiries</h2>
  <table>
    <thead><tr><th>Type</th><th>From</th><th>Email</th><th>Status</th><th>Date</th></tr></thead>
    <tbody>
      <?php foreach ($recentInquiries as $row): ?>
        <tr>
          <td><span class="badge"><?= Str::e((string) $row['type']) ?></span></td>
          <td><?= Str::e((string) ($row['full_name'] ?: trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')))) ?></td>
          <td><?= Str::e((string) $row['email']) ?></td>
          <td><?= Str::e((string) $row['status']) ?></td>
          <td><?= Str::e((string) $row['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p><a href="<?= Str::e(Url::to('admin/inquiries')) ?>">View all inquiries</a></p>
</div>

<div class="panel">
  <h2>Recent donations</h2>
  <table>
    <thead><tr><th>Donor</th><th>Amount</th><th>Status</th><th>Ref</th><th>Date</th></tr></thead>
    <tbody>
      <?php if ($recentDonations === []): ?>
        <tr><td colspan="5" class="muted">No donations yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($recentDonations as $row): ?>
        <tr>
          <td><?= Str::e((string) $row['donor_name']) ?></td>
          <td>₦<?= number_format((float) $row['amount'], 2) ?></td>
          <td><?= Str::e((string) $row['status']) ?></td>
          <td><?= Str::e((string) $row['flutterwave_tx_ref']) ?></td>
          <td><?= Str::e((string) $row['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
