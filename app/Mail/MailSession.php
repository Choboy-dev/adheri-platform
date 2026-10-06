<?php

declare(strict_types=1);

namespace Adheri\Mail;

use Adheri\Config;

/**
 * Dedicated mail session — separate from site Auth / SESSION_NAME.
 */
final class MailSession
{
    private const NAME = 'mf_mail_session';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (session_name() === self::NAME) {
                return;
            }
            // Do not collide with site session on shared PHP process.
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            $secure = str_starts_with((string) Config::get('APP_URL', ''), 'https://');
            session_name(self::NAME);
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'cookie_secure' => $secure,
            ]);
        }
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['mail_flash'] = ['type' => $type, 'message' => $message];
    }

    /** @return array{type:string,message:string}|null */
    public static function pullFlash(): ?array
    {
        $flash = $_SESSION['mail_flash'] ?? null;
        unset($_SESSION['mail_flash']);
        return is_array($flash) ? $flash : null;
    }

    public static function clientIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return is_string($ip) ? substr($ip, 0, 45) : '0.0.0.0';
    }

    public static function recordLoginAttempt(): void
    {
        \Adheri\Database::execute(
            'INSERT INTO mail_login_attempts (ip_address) VALUES (:ip)',
            ['ip' => self::clientIp()]
        );
    }

    public static function tooManyLoginAttempts(int $max = 8, int $minutes = 15): bool
    {
        $mins = max(1, $minutes);
        $row = \Adheri\Database::fetch(
            "SELECT COUNT(*) AS c FROM mail_login_attempts
             WHERE ip_address = :ip AND attempted_at >= DATE_SUB(NOW(), INTERVAL {$mins} MINUTE)",
            ['ip' => self::clientIp()]
        );
        return (int) ($row['c'] ?? 0) >= $max;
    }
}
