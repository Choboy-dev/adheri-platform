<?php

declare(strict_types=1);

/**
 * Set (or reset) a CMS admin password from the command line.
 *
 * Usage: php scripts/set-admin-password.php admin@example.org
 * The password is read from STDIN so it never lands in shell history.
 */

$root = dirname(__DIR__);

require $root . '/app/Config.php';
require $root . '/app/Database.php';

use Adheri\Config;
use Adheri\Database;

$email = strtolower(trim((string) ($argv[1] ?? '')));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php scripts/set-admin-password.php admin@example.org\n");
    exit(1);
}

fwrite(STDOUT, 'New password (min 12 chars): ');
if (DIRECTORY_SEPARATOR === '/') {
    shell_exec('stty -echo');
}
$password = rtrim((string) fgets(STDIN), "\r\n");
if (DIRECTORY_SEPARATOR === '/') {
    shell_exec('stty echo');
}
fwrite(STDOUT, "\n");

if (strlen($password) < 12) {
    fwrite(STDERR, "Password must be at least 12 characters.\n");
    exit(1);
}

Config::load($root);
Database::boot();

$updated = Database::execute(
    'UPDATE admin_users SET password_hash = :h, is_active = 1 WHERE email = :e',
    ['h' => password_hash($password, PASSWORD_DEFAULT), 'e' => $email]
);

if ($updated === 0) {
    fwrite(STDERR, "No admin with email {$email}. Check database/seed.sql or insert one first.\n");
    exit(1);
}

echo "Password updated for {$email}\n";
