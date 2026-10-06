<?php

declare(strict_types=1);

/**
 * Apply database/migrations/mail_*.sql once (tracked in mail_migrations).
 *
 * Usage: php scripts/run-mail-migrations.php
 */

$root = dirname(__DIR__);

require $root . '/app/Config.php';
require $root . '/app/Database.php';

use Adheri\Config;
use Adheri\Database;

Config::load($root);
Database::boot();
$pdo = Database::pdo();
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS mail_migrations (
        id VARCHAR(120) NOT NULL PRIMARY KEY,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$dir = $root . '/database/migrations';
$files = glob($dir . '/mail_*.sql') ?: [];
sort($files);

foreach ($files as $file) {
    $id = basename($file);
    $stmt = $pdo->prepare('SELECT id FROM mail_migrations WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    if ($exists) {
        echo "skip {$id}\n";
        continue;
    }

    $sql = trim((string) file_get_contents($file));
    // Strip pure comment files
    $executable = preg_replace('/^\s*--.*$/m', '', $sql) ?? '';
    $executable = trim($executable);
    if ($executable !== '') {
        $pdo->exec($sql);
    }

    $ins = $pdo->prepare('INSERT INTO mail_migrations (id) VALUES (:id)');
    $ins->execute(['id' => $id]);
    $ins->closeCursor();
    echo "applied {$id}\n";
}

echo "done\n";
