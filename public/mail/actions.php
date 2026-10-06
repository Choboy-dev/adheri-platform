<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$user = $mail->requireUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $mail->url());
    exit;
}

require_csrf();
$action = (string) ($_POST['action'] ?? '');
$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
    || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
    || in_array($action, ['star', 'discard_draft', 'cancel_schedule'], true);

$ids = [];
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    foreach ($_POST['ids'] as $id) {
        $ids[] = (int) $id;
    }
} elseif (isset($_POST['id'])) {
    $ids[] = (int) $_POST['id'];
}

$return = $mail->safeReturnUrl((string) ($_POST['return_to'] ?? $mail->path()));

try {
    switch ($action) {
        case 'star':
            $id = (int) ($_POST['id'] ?? 0);
            $on = $mail->toggleStar($user, $id);
            if ($wantsJson) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => true, 'starred' => $on], JSON_THROW_ON_ERROR);
                exit;
            }
            break;
        case 'discard_draft':
            $id = (int) ($_POST['id'] ?? 0);
            $msg = $mail->getMessage($user, $id);
            if ($msg && $msg['folder'] === 'drafts') {
                $mail->deleteMessages($user, [$id]);
            }
            if ($wantsJson) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => true], JSON_THROW_ON_ERROR);
                exit;
            }
            break;
        case 'cancel_schedule':
            $id = (int) ($_POST['id'] ?? 0);
            $res = $mail->cancelScheduled($user, $id);
            if ($wantsJson) {
                header('Content-Type: application/json');
                echo json_encode($res, JSON_THROW_ON_ERROR);
                exit;
            }
            break;
        case 'archive':
            $mail->moveMessages($user, $ids, 'archive');
            break;
        case 'trash':
            $mail->moveMessages($user, $ids, 'trash');
            break;
        case 'spam':
            $mail->moveMessages($user, $ids, 'spam');
            break;
        case 'not_spam':
            $mail->moveMessages($user, $ids, 'inbox');
            break;
        case 'delete':
            $mail->deleteMessages($user, $ids);
            break;
        case 'mark_read':
            $mail->bulkSetRead($user, $ids, true);
            break;
        case 'mark_unread':
            $mail->bulkSetRead($user, $ids, false);
            break;
        default:
            throw new RuntimeException('Unknown action');
    }
} catch (Throwable $e) {
    if ($wantsJson) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_THROW_ON_ERROR);
        exit;
    }
    Adheri\Mail\MailSession::flash('error', $e->getMessage());
}

header('Location: ' . $return);
exit;
