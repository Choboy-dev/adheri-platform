<?php

declare(strict_types=1);

/**
 * JSON-only send endpoint — avoids multipart/mod_security 500s on shared hosting.
 */

require __DIR__ . '/bootstrap.php';

@ini_set('memory_limit', '256M');
@ini_set('max_execution_time', '120');

header('Content-Type: application/json; charset=utf-8');

$log = static function (string $message, array $context = []) use ($mail): void {
    try {
        $mail->ensureStorage();
        $line = date('c') . ' ' . $message;
        if ($context !== []) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        }
        @file_put_contents(
            Adheri\Config::root() . '/storage/mail/logs/send-errors.log',
            $line . "\n",
            FILE_APPEND
        );
    } catch (Throwable) {
    }
};

$respond = static function (bool $ok, string $message, ?string $redirect = null, array $extra = []): void {
    http_response_code(200);
    echo json_encode([
        'ok' => $ok,
        'message' => $message,
        'redirect' => $redirect,
    ] + $extra, JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
};

register_shutdown_function(static function () use ($log): void {
    $err = error_get_last();
    if ($err === null) {
        return;
    }
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($err['type'], $fatalTypes, true)) {
        return;
    }
    $log('send_fatal', $err);
    if (!headers_sent()) {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false,
            'message' => 'Server error while sending: ' . ($err['message'] ?? 'unknown'),
            'redirect' => null,
        ], JSON_INVALID_UTF8_SUBSTITUTE);
    }
});

try {
    $log('send_hit', [
        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'ct' => $_SERVER['CONTENT_TYPE'] ?? '',
        'len' => $_SERVER['CONTENT_LENGTH'] ?? '',
    ]);

    $user = $mail->currentUser();
    if ($user === null) {
        $respond(false, 'Your session expired. Sign in again.', $mail->url('login'));
    }
    if ((int) $user['must_change_password'] === 1) {
        $respond(false, 'Change your password before sending mail.', $mail->url('change-password'));
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        $respond(false, 'POST required');
    }

    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        // Fallback to form fields if a client posts urlencoded.
        $data = $_POST;
    }
    if (!is_array($data) || $data === []) {
        $respond(false, 'Invalid send payload.');
    }

    $csrf = $data['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!Adheri\Mail\MailCsrf::validate(is_string($csrf) ? $csrf : null)) {
        $respond(false, 'Your session expired. Refresh the page and try again.');
    }

    $action = (string) ($data['action'] ?? 'send');
    $files = []; // attachments already staged via /mail/upload

    if ($action === 'draft') {
        $res = $mail->saveDraft($user, $data, $files);
        if (!$res['ok']) {
            $respond(false, (string) ($res['error'] ?? 'Could not save draft'), null, ['id' => $res['id'] ?? null]);
        }
        $respond(true, 'Draft saved.', $mail->safeReturnUrl((string) ($data['return_to'] ?? $mail->path())), ['id' => $res['id'] ?? null]);
    }

    $res = $mail->sendMessage($user, $data, $files);
    if (!$res['ok']) {
        $log('send_failed', ['error' => $res['error'] ?? '', 'id' => $res['id'] ?? null]);
        $redirect = !empty($res['id'])
            ? $mail->url() . '?compose=draft&draft=' . (int) $res['id']
            : $mail->url();
        $respond(false, (string) ($res['error'] ?? 'Send failed'), $redirect, ['id' => $res['id'] ?? null]);
    }

    $folder = !empty($data['scheduled_at']) ? 'scheduled' : 'sent';
    $respond(
        true,
        $folder === 'scheduled' ? 'Message scheduled.' : 'Message sent.',
        $mail->url() . '?folder=' . $folder,
        ['id' => $res['id'] ?? null, 'folder' => $folder]
    );
} catch (Throwable $e) {
    $log('send_exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    $respond(false, $e->getMessage() !== '' ? $e->getMessage() : 'Send failed unexpectedly.');
}
