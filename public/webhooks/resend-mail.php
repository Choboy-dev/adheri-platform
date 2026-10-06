<?php

declare(strict_types=1);

/**
 * Resend inbound webhook for ADHERI Mail.
 * URL: {APP_URL}/webhooks/resend-mail
 */

$root = dirname(__DIR__, 2);

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'Adheri\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $path = $root . '/app/' . $relative . '.php';
    if (is_file($path)) {
        require $path;
    }
});

use Adheri\Config;
use Adheri\Database;
use Adheri\Mail\MailService;
use Adheri\Mail\MailSession;

Config::load($root);
Database::boot();

$payload = file_get_contents('php://input') ?: '';
$headers = [];
foreach ($_SERVER as $k => $v) {
    if (str_starts_with($k, 'HTTP_')) {
        $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k, 5)))));
        $headers[$name] = $v;
        $headers[strtolower(str_replace('_', '-', substr($k, 5)))] = $v;
    }
}

$mail = new MailService();
$mail->ensureStorage();
$result = $mail->processInboundWebhook($payload, $headers);

if (empty($result['ok'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => $result['error'] ?? 'failed'], JSON_THROW_ON_ERROR);
    exit;
}

header('Content-Type: application/json');
echo json_encode(['ok' => true], JSON_THROW_ON_ERROR);
