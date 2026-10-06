<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = $mail->requireUser();
$id = (int) ($_GET['id'] ?? 0);
$att = $mail->getAttachment($user, $id);
if ($att === null) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$path = \Adheri\Config::root() . '/' . ltrim((string) $att['storage_path'], '/');
if (!is_file($path)) {
    http_response_code(404);
    echo 'File missing';
    exit;
}

$filename = (string) $att['filename'];
$safe = str_replace(['"', "\r", "\n"], '', $filename);
header('Content-Type: ' . ((string) $att['content_type'] ?: 'application/octet-stream'));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="' . $safe . '"');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
exit;
