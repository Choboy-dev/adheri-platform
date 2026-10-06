<?php

declare(strict_types=1);

namespace Adheri;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function boot(): void
    {
        if (self::$pdo !== null) {
            return;
        }

        $host = Config::get('DB_HOST', '127.0.0.1');
        $port = Config::get('DB_PORT', '3306');
        $name = Config::get('DB_DATABASE', 'adheri');
        $user = Config::get('DB_USERNAME', 'root');
        $pass = Config::get('DB_PASSWORD', '') ?? '';

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            if (Config::bool('APP_DEBUG')) {
                throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
            }
            throw new RuntimeException('Database connection failed.', 0, $e);
        }
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::boot();
        }
        return self::$pdo;
    }

    /** @param array<string, mixed> $params */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<string, mixed> $params
     *  @return list<array<string, mixed>>
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $params */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function lastId(): string
    {
        return self::pdo()->lastInsertId();
    }
}
