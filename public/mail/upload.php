<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

@ini_set('memory_limit', '256M');
@ini_set('max_execution_time', '120');

header('Content-Type: application/json; charset=utf-8');

$respond = static function (bool $ok, string $message, array $extra = []): void {
    http_response_code(200);
    echo json_encode(['ok' => $ok, 'message' => $message] + $extra, JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
};

try {
    $user = $mail->currentUser();
    if ($user === null) {
        $respond(false, 'Your session expired. Sign in again.');
    }
    if ((int) $user['must_change_password'] === 1) {
        $respond(false, 'Change your password before uploading.');
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $respond(false, 'POST required');
    }

    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > 0 && $_POST === [] && $_FILES === []) {
        $respond(false, 'File is too large for the server upload limit. Try a smaller image (under 2 MB) or compress it first.');
    }

    $csrf = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!Adheri\Mail\MailCsrf::validate(is_string($csrf) ? $csrf : null)) {
        $respond(false, 'Your session expired. Refresh the page and try again.');
    }

    $file = $_FILES['file'] ?? null;
    if (!is_array($file)) {
        $respond(false, 'No file received.');
    }

    $res = $mail->stageOutgoingAttachment($user, $file);
    if (!$res['ok']) {
        $respond(false, (string) ($res['error'] ?? 'Upload failed'));
    }
    $respond(true, 'Uploaded', [
        'token' => $res['token'] ?? null,
        'filename' => $res['filename'] ?? null,
        'size' => $res['size'] ?? null,
    ]);
} catch (Throwable $e) {
    $respond(false, $e->getMessage() !== '' ? $e->getMessage() : 'Upload failed unexpectedly.');
}
