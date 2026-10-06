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

header('Content-Type: application/json; charset=utf-8');

Config::load($root);
Database::boot();

$token = $_SERVER['HTTP_X_MAIL_SETUP_TOKEN'] ?? '';
$expected = (string) Config::get('MAIL_SETUP_TOKEN', '');
if ($expected === '' || !hash_equals($expected, (string) $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST required']);
    exit;
}

$pdo = Database::pdo();
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS mail_migrations (
        id VARCHAR(120) NOT NULL PRIMARY KEY,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$dir = $root . '/database/migrations';
$files = glob($dir . '/mail_*.sql') ?: [];
sort($files);
$applied = [];
foreach ($files as $file) {
    $id = basename($file);
    $stmt = $pdo->prepare('SELECT id FROM mail_migrations WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    if ($stmt->fetch()) {
        continue;
    }
    $pdo->exec((string) file_get_contents($file));
    $ins = $pdo->prepare('INSERT INTO mail_migrations (id) VALUES (:id)');
    $ins->execute(['id' => $id]);
    $applied[] = $id;
}

$mail = new MailService();
$mail->ensureStorage();

$email = (string) Config::get('MAIL_ADMIN_EMAIL', '');
$name = (string) Config::get('MAIL_ADMIN_DISPLAY_NAME', 'ADHERI');
$pass = (string) Config::get('MAIL_ADMIN_PASSWORD', '');

if ($email === '' || $pass === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Set MAIL_ADMIN_EMAIL and MAIL_ADMIN_PASSWORD']);
    exit;
}

$existing = $mail->findUserByEmail($email);
if ($existing) {
    Database::execute(
        'UPDATE mail_users SET display_name = :n, role = \'admin\', is_active = 1, password_hash = :h, must_change_password = 1 WHERE id = :id',
        ['n' => $name, 'h' => password_hash($pass, PASSWORD_DEFAULT), 'id' => (int) $existing['id']]
    );
    $id = (int) $existing['id'];
} else {
    $id = $mail->createUser($email, $name, $pass, 'admin');
}

echo json_encode([
    'ok' => true,
    'admin_id' => $id,
    'migrations' => $applied,
    'note' => 'Remove MAIL_SETUP_TOKEN from .env after setup',
], JSON_THROW_ON_ERROR);
