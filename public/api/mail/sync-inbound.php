<?php

declare(strict_types=1);

$root = dirname(__DIR__, 3);

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
use Adheri\Mail\MailCsrf;

Config::load($root);
Database::boot();
MailSession::start();

$mail = new MailService();
$token = $_SERVER['HTTP_X_MAIL_SETUP_TOKEN'] ?? '';
$expected = (string) Config::get('MAIL_SETUP_TOKEN', '');
$authed = false;

if ($expected !== '' && hash_equals($expected, (string) $token)) {
    $authed = true;
} else {
    $user = $mail->currentUser();
    $authed = $user !== null && ($user['role'] ?? '') === 'admin';
}

if (!$authed) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$result = $mail->syncInboundFromResend(50);
header('Content-Type: application/json');
echo json_encode($result, JSON_THROW_ON_ERROR);
