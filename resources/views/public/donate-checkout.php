<?php

use Adheri\Support\Str;
use Adheri\Support\Url;

/** @var array<string, mixed> $checkout */
/** @var string $publicKey */
/** @var bool $gatewayConfigured */
/** @var string $redirectUrl */
?>
<section class="section" style="padding-top:8rem">
  <div class="wrap" style="max-width:560px">
    <div class="form-panel">
      <h1 style="font-family:var(--font-display);font-size:2rem;margin:0 0 0.75rem">Complete your donation</h1>
      <p>Amount: <strong>₦<?= number_format((float) $checkout['amount']) ?></strong></p>
      <p>Reference: <code><?= Str::e((string) $checkout['tx_ref']) ?></code></p>
      <?php if (!$gatewayConfigured): ?>
        <div class="alert alert-error">Flutterwave public key missing. Donation recorded as pending only.</div>
        <a class="btn btn-secondary" href="<?= Str::e(Url::to('donate')) ?>">Back<span class="btn-icon" aria-hidden="true">↗</span></a>
      <?php else: ?>
        <button class="btn btn-primary" id="pay-button" type="button">Pay with Flutterwave<span class="btn-icon" aria-hidden="true">↗</span></button>
        <script src="https://checkout.flutterwave.com/v3.js"></script>
        <script>
          document.getElementById('pay-button')?.addEventListener('click', function () {
            FlutterwaveCheckout({
              public_key: <?= json_encode($publicKey) ?>,
              tx_ref: <?= json_encode($checkout['tx_ref']) ?>,
              amount: <?= json_encode((float) $checkout['amount']) ?>,
              currency: <?= json_encode($checkout['currency'] ?? 'NGN') ?>,
              payment_options: 'card,banktransfer,ussd',
              customer: {
                email: <?= json_encode($checkout['email']) ?>,
                name: <?= json_encode($checkout['name']) ?>,
                phone_number: <?= json_encode($checkout['phone'] ?? '') ?>
              },
              customizations: {
                title: 'ADHERI Donation',
                description: 'Support Advocates for Health & Rights of Women and Youths Initiative'
              },
              callback: function (data) {
                window.location.href = <?= json_encode($redirectUrl) ?> + '?transaction_id=' + encodeURIComponent(data.transaction_id || '');
              },
              onclose: function () {
                window.location.href = <?= json_encode(Url::to('donate/failed')) ?>;
              }
            });
          });
        </script>
      <?php endif; ?>
    </div>
  </div>
</section>
