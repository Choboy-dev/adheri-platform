<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = $mail->requireUser();
$state = $mail->inboxState($user);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => true,
    'latest_id' => $state['latest_id'],
    'unread' => $state['unread'],
], JSON_THROW_ON_ERROR);
