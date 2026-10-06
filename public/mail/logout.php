<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$mail->requireUser();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $mail->url());
    exit;
}
require_csrf();
$mail->logout();
header('Location: ' . $mail->url('login'));
exit;
