<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

@ini_set('memory_limit', '256M');
@ini_set('max_execution_time', '120');

$wantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
    || strcasecmp((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''), 'XMLHttpRequest') === 0;

$logSendError = static function (string $message, array $context = []) use ($mail): void {
    try {
        $mail->ensureStorage();
        $line = date('c') . ' ' . $message;
        if ($context !== []) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        }
        $line .= "\n";
        $path = Adheri\Config::root() . '/storage/mail/logs/send-errors.log';
        @file_put_contents($path, $line, FILE_APPEND);
    } catch (Throwable) {
        // ignore logging failures
    }
};

$respond = static function (bool $ok, string $message, ?string $redirect = null, array $extra = []) use ($wantsJson, $mail): void {
    if ($wantsJson) {
        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'ok' => $ok,
            'message' => $message,
            'redirect' => $redirect,
        ] + $extra, JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
    if ($message !== '') {
        Adheri\Mail\MailSession::flash($ok ? 'success' : 'error', $message);
    }
    header('Location: ' . ($redirect ?? $mail->url()));
    exit;
};

register_shutdown_function(static function () use ($wantsJson, $logSendError): void {
    $err = error_get_last();
    if ($err === null) {
        return;
    }
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($err['type'], $fatalTypes, true)) {
        return;
    }
    $logSendError('fatal', $err);
    if (!$wantsJson || headers_sent()) {
        return;
    }
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => false,
        'message' => 'Server error while sending: ' . ($err['message'] ?? 'unknown'),
        'redirect' => null,
    ], JSON_INVALID_UTF8_SUBSTITUTE);
});

try {
    $user = $mail->currentUser();
    if ($user === null) {
        if ($wantsJson) {
            $respond(false, 'Your session expired. Sign in again.', $mail->url('login'));
        }
        header('Location: ' . $mail->url('login'));
        exit;
    }
    if ((int) $user['must_change_password'] === 1) {
        $respond(false, 'Change your password before sending mail.', $mail->url('change-password'));
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $respond(false, '', $mail->url() . '?compose=new');
    }

    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > 0 && $_POST === [] && $_FILES === []) {
        $respond(
            false,
            'Message is too large for the server to accept (attachments may exceed the upload limit). Try smaller files or fewer attachments.',
            $mail->url() . '?compose=new'
        );
    }

    $csrf = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!Adheri\Mail\MailCsrf::validate(is_string($csrf) ? $csrf : null)) {
        $respond(false, 'Your session expired. Refresh the page and try again.', $mail->url());
    }
    $action = (string) ($_POST['action'] ?? 'send');
    $return = $mail->safeReturnUrl((string) ($_POST['return_to'] ?? $mail->path()));

    $stashComposeRetry = static function () use ($mail): void {
        Adheri\Mail\MailSession::set('mail_compose_retry', [
            'to' => (string) ($_POST['to'] ?? ''),
            'cc' => (string) ($_POST['cc'] ?? ''),
            'bcc' => (string) ($_POST['bcc'] ?? ''),
            'subject' => (string) ($_POST['subject'] ?? ''),
            'html' => (string) ($_POST['html'] ?? ''),
            'draft_id' => ($_POST['draft_id'] ?? '') !== '' ? (int) $_POST['draft_id'] : '',
            'in_reply_to' => (string) ($_POST['in_reply_to'] ?? ''),
        ]);
    };

    if ($action === 'draft') {
        $res = $mail->saveDraft($user, $_POST, $_FILES);
        if (!$res['ok']) {
            $stashComposeRetry();
            $redirect = !empty($res['id'])
                ? $mail->url() . '?compose=draft&draft=' . (int) $res['id']
                : $mail->url();
            $respond(false, (string) ($res['error'] ?? 'Could not save draft'), $redirect, ['id' => $res['id'] ?? null]);
        }
        $respond(true, 'Draft saved.', $return, ['id' => $res['id'] ?? null]);
    }

    $res = $mail->sendMessage($user, $_POST, $_FILES);
    if (!$res['ok']) {
        $logSendError('send_failed', ['error' => $res['error'] ?? '', 'id' => $res['id'] ?? null]);
        $redirect = !empty($res['id'])
            ? $mail->url() . '?compose=draft&draft=' . (int) $res['id']
            : $mail->url();
        if (empty($res['id'])) {
            $stashComposeRetry();
        }
        $respond(false, (string) ($res['error'] ?? 'Send failed'), $redirect, ['id' => $res['id'] ?? null]);
    }

    $folder = !empty($_POST['scheduled_at']) ? 'scheduled' : 'sent';
    $message = $folder === 'scheduled' ? 'Message scheduled.' : 'Message sent.';
    $redirect = $mail->url() . '?folder=' . $folder;
    $respond(true, $message, $redirect, ['id' => $res['id'] ?? null, 'folder' => $folder]);
} catch (Throwable $e) {
    $logSendError('exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    try {
        Adheri\Mail\MailSession::set('mail_compose_retry', [
            'to' => (string) ($_POST['to'] ?? ''),
            'cc' => (string) ($_POST['cc'] ?? ''),
            'bcc' => (string) ($_POST['bcc'] ?? ''),
            'subject' => (string) ($_POST['subject'] ?? ''),
            'html' => (string) ($_POST['html'] ?? ''),
            'draft_id' => ($_POST['draft_id'] ?? '') !== '' ? (int) $_POST['draft_id'] : '',
            'in_reply_to' => (string) ($_POST['in_reply_to'] ?? ''),
        ]);
    } catch (Throwable) {
        // ignore
    }
    $respond(false, $e->getMessage() !== '' ? $e->getMessage() : 'Send failed unexpectedly.', $mail->url());
}
