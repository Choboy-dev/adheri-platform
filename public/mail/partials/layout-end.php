<?php

declare(strict_types=1);

/** @var array<string, mixed> $user */
/** @var Adheri\Mail\MailService $mail */

$compose = $mail->composeContext($user, $_GET);
$signatures = $mail->listSignatures($user);
$scheduleOptions = $mail->scheduleOptions();
require __DIR__ . '/compose-panel.php';
?>
  </div><!-- .mail-main -->
</div><!-- .mail-shell -->
<div class="mail-sidebar-backdrop" data-mail-sidebar-backdrop hidden></div>
<script src="<?= mail_h(mail_asset('js/mail-dropdown.js')) ?>?v=1" defer></script>
<script src="<?= mail_h(mail_asset('js/mail-modal.js')) ?>?v=1" defer></script>
<script src="<?= mail_h(mail_asset('js/mail.js')) ?>?v=19" defer></script>
</body>
</html>
