<?php

declare(strict_types=1);

namespace Adheri\Modules\Auth;

use Adheri\Config;
use Adheri\Database;
use Adheri\Modules\Notifications\ResendClient;
use DateInterval;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

/**
 * OTP password recovery for Admin CMS and ADHERI Mail.
 *
 * Deep module: callers only need start → verifyOtp → resetPassword.
 * OTP can go to the account email or the account's personal RecoveryEmail.
 */
final class PasswordRecoveryService
{
    public const REALM_ADMIN = 'admin';
    public const REALM_MAIL = 'mail';

    /** Send OTP to the account's personal RecoveryEmail. */
    public const CHANNEL_RECOVERY_EMAIL = 'recovery_email';
    /** Send OTP to the login / mailbox address. */
    public const CHANNEL_ACCOUNT_EMAIL = 'account_email';

    /** @deprecated Use CHANNEL_RECOVERY_EMAIL */
    public const CHANNEL_RECOVERY_INBOX = 'recovery_email';

    private const OTP_TTL_MINUTES = 10;
    private const RESET_TTL_MINUTES = 15;
    private const MAX_ATTEMPTS = 5;
    private const MIN_PASSWORD_LEN = 12;
    private const REQUEST_COOLDOWN_SECONDS = 60;

    public function __construct(
        private readonly ResendClient $mailer = new ResendClient(),
    ) {
    }

    /**
     * Validate and normalize a personal RecoveryEmail for an account.
     *
     * @return array{ok:bool,email?:string,error?:string}
     */
    public function normalizeRecoveryEmail(string $recoveryEmail, string $accountEmail): array
    {
        $recoveryEmail = strtolower(trim($recoveryEmail));
        $accountEmail = strtolower(trim($accountEmail));

        if (!filter_var($recoveryEmail, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Enter a valid recovery email address.'];
        }
        if ($recoveryEmail === $accountEmail) {
            return [
                'ok' => false,
                'error' => 'Use a different personal email — not the same as this account address.',
            ];
        }

        return ['ok' => true, 'email' => $recoveryEmail];
    }

    /**
     * @return array{ok:bool,challenge_id?:string,message?:string,error?:string,delivery_email_masked?:string}
     */
    public function start(string $realm, string $accountEmail, string $channel): array
    {
        $this->ensureSchema();

        try {
            $realm = $this->normalizeRealm($realm);
            $channel = $this->normalizeChannel($channel);
        } catch (RuntimeException) {
            return ['ok' => false, 'error' => 'Choose a valid delivery option.'];
        }

        $accountEmail = strtolower(trim($accountEmail));
        if (!filter_var($accountEmail, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Enter a valid email address.'];
        }

        $account = $this->findAccount($realm, $accountEmail);
        if ($account === null) {
            return [
                'ok' => false,
                'error' => 'No active account was found for that email.',
            ];
        }

        if ($this->recentChallengeExists($realm, $accountEmail)) {
            return [
                'ok' => false,
                'error' => 'Please wait about a minute before requesting another code.',
            ];
        }

        if ($channel === self::CHANNEL_RECOVERY_EMAIL) {
            $personal = trim((string) ($account['recovery_email'] ?? ''));
            if ($personal === '' || !filter_var($personal, FILTER_VALIDATE_EMAIL)) {
                return [
                    'ok' => false,
                    'error' => 'This account has no recovery email on file yet. Use the account email option, or sign in and add a recovery email.',
                ];
            }
            $deliveryEmail = strtolower($personal);
        } else {
            $deliveryEmail = $accountEmail;
        }

        $otp = (string) random_int(100000, 999999);
        $publicId = $this->uuid();
        $expires = (new DateTimeImmutable('now'))->add(new DateInterval('PT' . self::OTP_TTL_MINUTES . 'M'));

        Database::execute(
            'INSERT INTO password_reset_challenges
             (public_id, realm, account_email, account_user_id, delivery_channel, delivery_email, otp_hash, expires_at, max_attempts)
             VALUES
             (:pid, :realm, :email, :uid, :channel, :delivery, :hash, :expires, :max)',
            [
                'pid' => $publicId,
                'realm' => $realm,
                'email' => $accountEmail,
                'uid' => (int) $account['id'],
                'channel' => $channel,
                'delivery' => $deliveryEmail,
                'hash' => password_hash($otp, PASSWORD_DEFAULT),
                'expires' => $expires->format('Y-m-d H:i:s'),
                'max' => self::MAX_ATTEMPTS,
            ]
        );

        $sent = $this->sendOtpEmail(
            $deliveryEmail,
            $otp,
            $realm,
            $accountEmail,
            $channel === self::CHANNEL_RECOVERY_EMAIL
        );

        if (!$sent['ok']) {
            Database::execute(
                'DELETE FROM password_reset_challenges WHERE public_id = :pid',
                ['pid' => $publicId]
            );
            return [
                'ok' => false,
                'error' => $sent['error'] ?? 'Could not send the verification code. Try again shortly.',
            ];
        }

        return [
            'ok' => true,
            'challenge_id' => $publicId,
            'message' => 'A 6-digit code was sent. It expires in ' . self::OTP_TTL_MINUTES . ' minutes.',
            'delivery_email_masked' => $this->maskEmail($deliveryEmail),
        ];
    }

    /**
     * @return array{ok:bool,reset_token?:string,error?:string}
     */
    public function verifyOtp(string $challengeId, string $otp): array
    {
        $this->ensureSchema();
        $challengeId = trim($challengeId);
        $otp = preg_replace('/\s+/', '', trim($otp)) ?? '';
        if ($challengeId === '' || !preg_match('/^\d{6}$/', $otp)) {
            return ['ok' => false, 'error' => 'Enter the 6-digit code from your email.'];
        }

        $row = Database::fetch(
            'SELECT * FROM password_reset_challenges WHERE public_id = :pid LIMIT 1',
            ['pid' => $challengeId]
        );
        if ($row === null) {
            return ['ok' => false, 'error' => 'This reset request is invalid or has expired. Start again.'];
        }
        if ($row['consumed_at'] !== null || $row['verified_at'] !== null) {
            return ['ok' => false, 'error' => 'This code was already used. Start a new reset request.'];
        }
        if (strtotime((string) $row['expires_at']) < time()) {
            return ['ok' => false, 'error' => 'This code has expired. Request a new one.'];
        }
        if ((int) $row['attempts'] >= (int) $row['max_attempts']) {
            return ['ok' => false, 'error' => 'Too many incorrect attempts. Request a new code.'];
        }

        if (!password_verify($otp, (string) $row['otp_hash'])) {
            Database::execute(
                'UPDATE password_reset_challenges SET attempts = attempts + 1 WHERE id = :id',
                ['id' => (int) $row['id']]
            );
            $left = max(0, (int) $row['max_attempts'] - ((int) $row['attempts'] + 1));
            return [
                'ok' => false,
                'error' => $left > 0
                    ? ('Incorrect code. ' . $left . ' attempt' . ($left === 1 ? '' : 's') . ' left.')
                    : 'Too many incorrect attempts. Request a new code.',
            ];
        }

        $resetToken = bin2hex(random_bytes(32));
        $resetExpires = (new DateTimeImmutable('now'))->add(new DateInterval('PT' . self::RESET_TTL_MINUTES . 'M'));
        Database::execute(
            'UPDATE password_reset_challenges
             SET verified_at = NOW(), reset_token = :tok, expires_at = :exp
             WHERE id = :id',
            [
                'tok' => $resetToken,
                'exp' => $resetExpires->format('Y-m-d H:i:s'),
                'id' => (int) $row['id'],
            ]
        );

        return ['ok' => true, 'reset_token' => $resetToken];
    }

    /**
     * @return array{ok:bool,error?:string,realm?:string}
     */
    public function resetPassword(string $resetToken, string $password, string $confirm): array
    {
        $this->ensureSchema();
        $resetToken = trim($resetToken);
        if ($resetToken === '' || !preg_match('/^[a-f0-9]{64}$/', $resetToken)) {
            return ['ok' => false, 'error' => 'Invalid reset session. Start again.'];
        }
        if ($password !== $confirm) {
            return ['ok' => false, 'error' => 'Passwords do not match.'];
        }
        if (strlen($password) < self::MIN_PASSWORD_LEN) {
            return ['ok' => false, 'error' => 'Password must be at least ' . self::MIN_PASSWORD_LEN . ' characters.'];
        }

        $row = Database::fetch(
            'SELECT * FROM password_reset_challenges WHERE reset_token = :tok LIMIT 1',
            ['tok' => $resetToken]
        );
        if ($row === null || $row['verified_at'] === null) {
            return ['ok' => false, 'error' => 'Invalid reset session. Start again.'];
        }
        if ($row['consumed_at'] !== null) {
            return ['ok' => false, 'error' => 'This reset link was already used.'];
        }
        if (strtotime((string) $row['expires_at']) < time()) {
            return ['ok' => false, 'error' => 'This reset session has expired. Start again.'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $realm = (string) $row['realm'];
        $userId = (int) $row['account_user_id'];

        if ($realm === self::REALM_ADMIN) {
            Database::execute(
                'UPDATE admin_users SET password_hash = :h WHERE id = :id AND is_active = 1',
                ['h' => $hash, 'id' => $userId]
            );
        } elseif ($realm === self::REALM_MAIL) {
            Database::execute(
                'UPDATE mail_users SET password_hash = :h, must_change_password = 0 WHERE id = :id AND is_active = 1',
                ['h' => $hash, 'id' => $userId]
            );
        } else {
            return ['ok' => false, 'error' => 'Unknown account type.'];
        }

        Database::execute(
            'UPDATE password_reset_challenges SET consumed_at = NOW(), reset_token = NULL WHERE id = :id',
            ['id' => (int) $row['id']]
        );

        return ['ok' => true, 'realm' => $realm];
    }

    /** @return array{id:int,email:string,recovery_email:?string}|null */
    private function findAccount(string $realm, string $email): ?array
    {
        if ($realm === self::REALM_ADMIN) {
            $row = Database::fetch(
                'SELECT id, email, recovery_email FROM admin_users WHERE email = :email AND is_active = 1 LIMIT 1',
                ['email' => $email]
            );
        } else {
            $row = Database::fetch(
                'SELECT id, email, recovery_email FROM mail_users WHERE email = :email AND is_active = 1 LIMIT 1',
                ['email' => $email]
            );
        }
        if ($row === null) {
            return null;
        }
        $recovery = isset($row['recovery_email']) ? trim((string) $row['recovery_email']) : '';
        return [
            'id' => (int) $row['id'],
            'email' => (string) $row['email'],
            'recovery_email' => $recovery !== '' ? $recovery : null,
        ];
    }

    private function recentChallengeExists(string $realm, string $email): bool
    {
        $row = Database::fetch(
            'SELECT id FROM password_reset_challenges
             WHERE realm = :realm AND account_email = :email
               AND created_at >= DATE_SUB(NOW(), INTERVAL ' . self::REQUEST_COOLDOWN_SECONDS . ' SECOND)
             LIMIT 1',
            ['realm' => $realm, 'email' => $email]
        );
        return $row !== null;
    }

    /** @return array{ok:bool,error?:string} */
    private function sendOtpEmail(
        string $to,
        string $otp,
        string $realm,
        string $accountEmail,
        bool $isPersonalRecovery
    ): array {
        if (!$this->mailer->isConfigured()) {
            return ['ok' => false, 'error' => 'Email sending is not configured on the server.'];
        }

        $fromEmail = (string) Config::get('MAIL_FROM', 'info@advocatesforhealthandrights.org');
        $fromName = (string) Config::get('MAIL_FROM_NAME', 'ADHERI');
        $from = $fromName . ' <' . $fromEmail . '>';
        $label = $realm === self::REALM_ADMIN ? 'Website Admin' : 'ADHERI Mail';
        $subject = $label . ' password reset code';

        $extra = $isPersonalRecovery
            ? '<p style="margin:0 0 12px;color:#444">Requested for account <strong>'
                . htmlspecialchars($accountEmail, ENT_QUOTES, 'UTF-8')
                . '</strong>.</p>'
            : '';

        $html = '<div style="font-family:Arial,sans-serif;max-width:520px;line-height:1.5;color:#202124">'
            . '<p style="margin:0 0 12px">Use this one-time code to reset your ' . htmlspecialchars($label, ENT_QUOTES) . ' password:</p>'
            . $extra
            . '<p style="font-size:28px;letter-spacing:6px;font-weight:700;margin:16px 0">'
            . htmlspecialchars($otp, ENT_QUOTES) . '</p>'
            . '<p style="margin:0;color:#5f6368;font-size:14px">Expires in ' . self::OTP_TTL_MINUTES
            . ' minutes. If you did not request this, you can ignore this email.</p>'
            . '</div>';

        $text = "Your {$label} password reset code is {$otp}. It expires in "
            . self::OTP_TTL_MINUTES . " minutes.";

        return $this->mailer->send($from, [$to], $subject, $html, null, $text);
    }

    private function normalizeRealm(string $realm): string
    {
        $realm = strtolower(trim($realm));
        if (!in_array($realm, [self::REALM_ADMIN, self::REALM_MAIL], true)) {
            throw new RuntimeException('Invalid recovery realm.');
        }
        return $realm;
    }

    private function normalizeChannel(string $channel): string
    {
        $channel = strtolower(trim($channel));
        // Legacy form value from the old global-inbox flow.
        if ($channel === 'recovery_inbox') {
            $channel = self::CHANNEL_RECOVERY_EMAIL;
        }
        if (!in_array($channel, [self::CHANNEL_RECOVERY_EMAIL, self::CHANNEL_ACCOUNT_EMAIL], true)) {
            throw new RuntimeException('Invalid delivery option.');
        }
        return $channel;
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return '***';
        }
        $local = $parts[0];
        $domain = $parts[1];
        $keep = min(2, max(1, (int) floor(strlen($local) / 3)));
        return substr($local, 0, $keep) . str_repeat('*', max(3, strlen($local) - $keep)) . '@' . $domain;
    }

    private function uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        $h = bin2hex($b);
        return substr($h, 0, 8) . '-' . substr($h, 8, 4) . '-' . substr($h, 12, 4)
            . '-' . substr($h, 16, 4) . '-' . substr($h, 20, 12);
    }

    private function ensureSchema(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }

        Database::execute(
            "CREATE TABLE IF NOT EXISTS password_reset_challenges (
              id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
              public_id CHAR(36) NOT NULL,
              realm ENUM('admin','mail') NOT NULL,
              account_email VARCHAR(190) NOT NULL,
              account_user_id INT UNSIGNED NOT NULL,
              delivery_channel ENUM('recovery_email','account_email','recovery_inbox') NOT NULL,
              delivery_email VARCHAR(190) NOT NULL,
              otp_hash VARCHAR(255) NOT NULL,
              reset_token CHAR(64) NULL,
              attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
              max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 5,
              expires_at DATETIME NOT NULL,
              verified_at DATETIME NULL,
              consumed_at DATETIME NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY uq_password_reset_public_id (public_id),
              UNIQUE KEY uq_password_reset_token (reset_token),
              KEY idx_password_reset_lookup (realm, account_email, created_at),
              KEY idx_password_reset_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->ensureColumn('mail_users', 'recovery_email', 'VARCHAR(190) NULL AFTER email');
        $this->ensureColumn('admin_users', 'recovery_email', 'VARCHAR(190) NULL AFTER email');

        try {
            Database::execute(
                "ALTER TABLE password_reset_challenges
                 MODIFY delivery_channel ENUM('recovery_email','account_email','recovery_inbox') NOT NULL"
            );
        } catch (Throwable) {
            // Table may already match or lack permission — inserts still accept recovery_email once altered.
        }

        $ready = true;
    }

    private function ensureColumn(string $table, string $column, string $definition): void
    {
        $db = (string) Config::get('DB_DATABASE', 'adheri');
        $row = Database::fetch(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :col LIMIT 1',
            ['db' => $db, 'table' => $table, 'col' => $column]
        );
        if ($row !== null) {
            return;
        }
        Database::execute("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }
}
