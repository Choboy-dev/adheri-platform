<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = $mail->requireUser();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

require_csrf();
$res = $mail->storeInlineImage($user, $_FILES['image'] ?? $_FILES['file'] ?? []);
if (!$res['ok']) {
    http_response_code(400);
}
echo json_encode($res, JSON_THROW_ON_ERROR);
