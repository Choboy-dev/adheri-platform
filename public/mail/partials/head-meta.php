<?php

declare(strict_types=1);

/** @var string $title */
/** @var Adheri\Mail\MailService $mail */
mail_send_security_headers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="<?= mail_h(Adheri\Mail\MailCsrf::token()) ?>">
  <title><?= mail_h($title ?? $mail->appName()) ?></title>
  <link rel="stylesheet" href="<?= mail_h(mail_asset('css/mail-dropdown.css')) ?>?v=1">
  <link rel="stylesheet" href="<?= mail_h(mail_asset('css/mail-modal.css')) ?>?v=3">
  <link rel="stylesheet" href="<?= mail_h(mail_asset('css/mail.css')) ?>?v=24">
</head>
