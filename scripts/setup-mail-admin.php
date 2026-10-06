<?php

declare(strict_types=1);

/**
 * Create / update mail admin from env.
 * Usage: php scripts/setup-mail-admin.php
 */

$root = dirname(__DIR__);

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

require $root . '/scripts/run-mail-migrations.php';

use Adheri\Config;
use Adheri\Database;
use Adheri\Mail\MailService;

$mail = new MailService();
$mail->ensureStorage();

$email = (string) Config::get('MAIL_ADMIN_EMAIL', '');
$name = (string) Config::get('MAIL_ADMIN_DISPLAY_NAME', 'ADHERI');
$pass = (string) Config::get('MAIL_ADMIN_PASSWORD', '');

if ($email === '' || $pass === '') {
    fwrite(STDERR, "Set MAIL_ADMIN_EMAIL and MAIL_ADMIN_PASSWORD in .env\n");
    exit(1);
}

$existing = $mail->findUserByEmail($email);
if ($existing) {
    Database::execute(
        'UPDATE mail_users SET display_name = :n, role = \'admin\', is_active = 1, password_hash = :h, must_change_password = 1 WHERE id = :id',
        [
            'n' => $name,
            'h' => password_hash($pass, PASSWORD_DEFAULT),
            'id' => (int) $existing['id'],
        ]
    );
    echo "updated admin {$email}\n";
} else {
    $id = $mail->createUser($email, $name, $pass, 'admin');
    echo "created admin {$email} id={$id}\n";
}
