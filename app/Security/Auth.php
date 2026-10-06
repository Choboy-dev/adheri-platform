<?php

declare(strict_types=1);

namespace Adheri\Security;

use Adheri\Database;
use Adheri\Http\Response;
use Adheri\Modules\Auth\PasswordRecoveryService;
use Adheri\Support\Url;
use Throwable;

final class Auth
{
    private const SESSION_KEY = 'admin_user_id';

    public static function boot(): void
    {
        // session already active
    }

    public static function attempt(string $email, string $password): bool
    {
        self::ensureRecoveryEmailColumn();

        $user = Database::fetch(
            'SELECT * FROM admin_users WHERE email = :email AND is_active = 1 LIMIT 1',
            ['email' => strtolower(trim($email))]
        );

        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = (int) $user['id'];
        Database::execute(
            'UPDATE admin_users SET last_login_at = NOW() WHERE id = :id',
            ['id' => $user['id']]
        );
        return true;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function id(): ?int
    {
        $id = $_SESSION[self::SESSION_KEY] ?? null;
        return is_int($id) || ctype_digit((string) $id) ? (int) $id : null;
    }

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        $id = self::id();
        if ($id === null) {
            return null;
        }
        self::ensureRecoveryEmailColumn();
        return Database::fetch(
            'SELECT id, name, email, role, recovery_email FROM admin_users WHERE id = :id',
            ['id' => $id]
        );
    }

    /** @param array<string, mixed> $user */
    public static function needsAccountSetup(array $user): bool
    {
        $recovery = trim((string) ($user['recovery_email'] ?? ''));
        return $recovery === '' || !filter_var($recovery, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @return array{ok:bool,error?:string}
     */
    public static function saveRecoveryEmail(string $recoveryEmail): array
    {
        $user = self::user();
        if ($user === null) {
            return ['ok' => false, 'error' => 'Sign in required.'];
        }
        self::ensureRecoveryEmailColumn();
        $normalized = (new PasswordRecoveryService())
            ->normalizeRecoveryEmail($recoveryEmail, (string) $user['email']);
        if (!$normalized['ok']) {
            return ['ok' => false, 'error' => $normalized['error'] ?? 'Invalid recovery email.'];
        }
        Database::execute(
            'UPDATE admin_users SET recovery_email = :re WHERE id = :id',
            ['re' => $normalized['email'], 'id' => (int) $user['id']]
        );
        return ['ok' => true];
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        session_regenerate_id(true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Response::redirect(Url::to('admin/login'));
        }

        $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
        $onSetup = str_contains($path, '/admin/account-setup');
        $user = self::user();
        if ($user !== null && self::needsAccountSetup($user) && !$onSetup) {
            Response::redirect(Url::to('admin/account-setup'));
        }
    }

    private static function ensureRecoveryEmailColumn(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }
        try {
            $db = (string) (\Adheri\Config::get('DB_DATABASE', 'adheri'));
            $row = Database::fetch(
                'SELECT COLUMN_NAME FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = :db AND TABLE_NAME = \'admin_users\' AND COLUMN_NAME = \'recovery_email\' LIMIT 1',
                ['db' => $db]
            );
            if ($row === null) {
                Database::execute('ALTER TABLE admin_users ADD COLUMN recovery_email VARCHAR(190) NULL AFTER email');
            }
        } catch (Throwable) {
            // Will retry next request if DB momentarily unavailable.
        }
        $ready = true;
    }
}
