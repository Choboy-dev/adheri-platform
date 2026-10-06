<?php

declare(strict_types=1);

namespace Adheri\Mail;

use Adheri\Config;
use Adheri\Database;
use Adheri\Modules\Auth\PasswordRecoveryService;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

/**
 * Metro Mail domain module — private webmail for @MAIL_DOMAIN.
 * Separate from site Auth and ContactMailer.
 */
final class MailService
{
    public const PER_PAGE = 25;

    private const FOLDERS = ['inbox', 'sent', 'drafts', 'scheduled', 'archive', 'spam', 'trash'];

    private const ALLOWED_EXT = [
        'pdf', 'txt', 'csv', 'jpg', 'jpeg', 'png', 'gif', 'webp',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip',
    ];

    private const SOCIAL_DOMAINS = [
        'facebook.com', 'instagram.com', 'linkedin.com', 'tiktok.com', 'youtube.com',
        'twitter.com', 'x.com', 'pinterest.com', 'reddit.com', 'threads.net',
        'snapchat.com', 'discord.com', 'tumblr.com', 'quora.com', 'meetup.com',
        'nextdoor.com', 'bsky.app', 'mastodon.social', 'vk.com', 'weibo.com',
    ];

    private ResendMailClient $resend;

    public function __construct(?ResendMailClient $resend = null)
    {
        $this->resend = $resend ?? new ResendMailClient();
    }

    public function ensureStorage(): void
    {
        $root = Config::root();
        $dirs = [
            $root . '/storage/mail/inbound',
            $root . '/storage/mail/outbound',
            $root . '/storage/mail/drafts',
            $root . '/storage/mail/logs',
            $root . '/storage/uploads/mail-inline',
            $root . '/storage/uploads/mail-avatars',
            Config::get('PUBLIC_PATH') . '/uploads/mail-inline',
            Config::get('PUBLIC_PATH') . '/uploads/mail-avatars',
            Config::get('PUBLIC_PATH') . '/uploads/mail-attach',
            $root . '/storage/mail/staging',
            Config::get('PUBLIC_PATH') . '/uploads/mail-attach/staging',
        ];
        foreach ($dirs as $dir) {
            if ($dir && !is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        $this->ensureRecoveryEmailColumn();
    }

    public function domain(): string
    {
        return (string) Config::get('MAIL_DOMAIN', 'advocatesforhealthandrights.org');
    }

    public function appName(): string
    {
        return (string) Config::get('MAIL_APP_NAME', 'ADHERI Mail');
    }

    public function path(): string
    {
        $p = rtrim((string) Config::get('MAIL_PATH', '/mail'), '/');
        return $p === '' ? '/mail' : $p;
    }

    public function url(string $path = ''): string
    {
        $base = rtrim((string) Config::get('APP_URL', ''), '/');
        $mail = $this->path();
        $path = ltrim($path, '/');
        if ($path === '') {
            return $base . $mail;
        }
        return $base . $mail . '/' . $path;
    }

    public function safeReturnUrl(?string $url): string
    {
        $fallback = $this->url();
        if ($url === null || $url === '') {
            return $fallback;
        }
        $mailPath = $this->path();
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        if (is_string($path) && (str_starts_with($path, $mailPath) || str_starts_with($url, $mailPath))) {
            return str_starts_with($url, 'http') ? $url : $url;
        }
        return $fallback;
    }

    /** @return array<string, mixed>|null */
    public function findUserByEmail(string $email): ?array
    {
        return Database::fetch(
            'SELECT * FROM mail_users WHERE email = :email LIMIT 1',
            ['email' => strtolower(trim($email))]
        );
    }

    /** @return array<string, mixed>|null */
    public function findUserById(int $id): ?array
    {
        return Database::fetch('SELECT * FROM mail_users WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return array<string, mixed>|null */
    public function authenticate(string $email, string $password): ?array
    {
        if (MailSession::tooManyLoginAttempts()) {
            return null;
        }
        $user = $this->findUserByEmail($email);
        MailSession::recordLoginAttempt();
        if ($user === null || !(int) $user['is_active']) {
            return null;
        }
        if (!password_verify($password, (string) $user['password_hash'])) {
            return null;
        }
        return $user;
    }

    /** @param array<string, mixed> $user */
    public function login(array $user): void
    {
        MailSession::regenerate();
        MailSession::set('mail_user_id', (int) $user['id']);
        MailSession::set('mail_user_email', (string) $user['email']);
    }

    public function logout(): void
    {
        MailSession::forget('mail_user_id');
        MailSession::forget('mail_user_email');
        MailSession::forget('mail_compose_retry');
    }

    /** @return array<string, mixed>|null */
    public function currentUser(): ?array
    {
        $id = MailSession::get('mail_user_id');
        if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
            return null;
        }
        $user = $this->findUserById((int) $id);
        if ($user === null || !(int) $user['is_active']) {
            return null;
        }
        return $user;
    }

    /** @return array<string, mixed> */
    public function requireUser(): array
    {
        $user = $this->currentUser();
        if ($user === null) {
            header('Location: ' . $this->url('login'));
            exit;
        }
        if ($this->needsAccountSetup($user)) {
            $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
            if (!in_array($script, ['account-setup.php', 'change-password.php', 'logout.php'], true)) {
                header('Location: ' . $this->url('account-setup'));
                exit;
            }
        }
        return $user;
    }

    /** @param array<string, mixed> $user */
    public function needsAccountSetup(array $user): bool
    {
        if ((int) ($user['must_change_password'] ?? 0) === 1) {
            return true;
        }
        $recovery = trim((string) ($user['recovery_email'] ?? ''));
        return $recovery === '' || !filter_var($recovery, FILTER_VALIDATE_EMAIL);
    }

    public function changePassword(int $userId, string $newPassword): void
    {
        if (strlen($newPassword) < 12) {
            throw new RuntimeException('Password must be at least 12 characters.');
        }
        Database::execute(
            'UPDATE mail_users SET password_hash = :h, must_change_password = 0 WHERE id = :id',
            ['h' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $userId]
        );
    }

    /**
     * First-login / security setup: optional password change + required RecoveryEmail.
     *
     * @return array{ok:bool,error?:string}
     */
    public function completeAccountSetup(array $user, string $recoveryEmail, ?string $newPassword = null, ?string $confirm = null): array
    {
        $this->ensureRecoveryEmailColumn();

        $needsPassword = (int) ($user['must_change_password'] ?? 0) === 1;
        if ($needsPassword) {
            $newPassword = (string) $newPassword;
            $confirm = (string) $confirm;
            if ($newPassword === '' || $confirm === '') {
                return ['ok' => false, 'error' => 'Choose a new password to continue.'];
            }
            if ($newPassword !== $confirm) {
                return ['ok' => false, 'error' => 'Passwords do not match.'];
            }
            if (strlen($newPassword) < 12) {
                return ['ok' => false, 'error' => 'Password must be at least 12 characters.'];
            }
        }

        $normalized = (new PasswordRecoveryService())
            ->normalizeRecoveryEmail($recoveryEmail, (string) $user['email']);
        if (!$normalized['ok']) {
            return ['ok' => false, 'error' => $normalized['error'] ?? 'Invalid recovery email.'];
        }

        if ($needsPassword) {
            Database::execute(
                'UPDATE mail_users
                 SET password_hash = :h, must_change_password = 0, recovery_email = :re
                 WHERE id = :id',
                [
                    'h' => password_hash((string) $newPassword, PASSWORD_DEFAULT),
                    're' => $normalized['email'],
                    'id' => (int) $user['id'],
                ]
            );
        } else {
            Database::execute(
                'UPDATE mail_users SET recovery_email = :re WHERE id = :id',
                ['re' => $normalized['email'], 'id' => (int) $user['id']]
            );
        }

        return ['ok' => true];
    }

    public function updateRecoveryEmail(array $user, string $recoveryEmail): void
    {
        $this->ensureRecoveryEmailColumn();
        $normalized = (new PasswordRecoveryService())
            ->normalizeRecoveryEmail($recoveryEmail, (string) $user['email']);
        if (!$normalized['ok']) {
            throw new RuntimeException($normalized['error'] ?? 'Invalid recovery email.');
        }
        Database::execute(
            'UPDATE mail_users SET recovery_email = :re WHERE id = :id',
            ['re' => $normalized['email'], 'id' => (int) $user['id']]
        );
    }

    private function ensureRecoveryEmailColumn(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }
        $db = (string) Config::get('DB_DATABASE', 'adheri');
        $row = Database::fetch(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = :db AND TABLE_NAME = \'mail_users\' AND COLUMN_NAME = \'recovery_email\' LIMIT 1',
            ['db' => $db]
        );
        if ($row === null) {
            Database::execute('ALTER TABLE mail_users ADD COLUMN recovery_email VARCHAR(190) NULL AFTER email');
        }
        $ready = true;
    }

    /** @return array<string, mixed> */
    public function requireAdmin(): array
    {
        $user = $this->requireUser();
        if (($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
        return $user;
    }

    /** @param array<string, mixed> $user */
    public function userInitials(array $user): string
    {
        $name = trim((string) ($user['display_name'] ?? ''));
        if ($name === '') {
            $name = (string) ($user['email'] ?? '?');
        }
        $parts = preg_split('/\s+/', $name) ?: [];
        $ini = '';
        foreach (array_slice($parts, 0, 2) as $p) {
            $ini .= mb_strtoupper(mb_substr($p, 0, 1));
        }
        return $ini !== '' ? $ini : '?';
    }

    /** @return list<array<string, mixed>> */
    public function listUsers(): array
    {
        return Database::fetchAll('SELECT * FROM mail_users ORDER BY role ASC, email ASC');
    }

    public function createUser(string $email, string $displayName, string $tempPassword, string $role = 'user'): int
    {
        $email = strtolower(trim($email));
        $domain = $this->domain();
        if (!str_ends_with($email, '@' . $domain)) {
            throw new RuntimeException('Email must be @' . $domain);
        }
        if (strlen($tempPassword) < 12) {
            throw new RuntimeException('Temporary password must be at least 12 characters.');
        }
        if (!in_array($role, ['admin', 'user'], true)) {
            $role = 'user';
        }
        Database::execute(
            'INSERT INTO mail_users (email, display_name, password_hash, role, is_active, must_change_password)
             VALUES (:email, :name, :hash, :role, 1, 1)',
            [
                'email' => $email,
                'name' => $displayName,
                'hash' => password_hash($tempPassword, PASSWORD_DEFAULT),
                'role' => $role,
            ]
        );
        return (int) Database::lastId();
    }

    /** @param array<string, mixed> $data */
    public function updateUser(int $id, array $data): void
    {
        $user = $this->findUserById($id);
        if ($user === null) {
            throw new RuntimeException('User not found.');
        }

        $role = (string) ($data['role'] ?? $user['role']);
        $active = !empty($data['is_active']) ? 1 : 0;

        if ((string) $user['role'] === 'admin' && ($role !== 'admin' || $active === 0)) {
            $admins = Database::fetch(
                "SELECT COUNT(*) AS c FROM mail_users WHERE role = 'admin' AND is_active = 1 AND id != :id",
                ['id' => $id]
            );
            if ((int) ($admins['c'] ?? 0) < 1) {
                throw new RuntimeException('Cannot disable or demote the last active admin.');
            }
        }

        $params = [
            'id' => $id,
            'name' => (string) ($data['display_name'] ?? $user['display_name']),
            'role' => in_array($role, ['admin', 'user'], true) ? $role : 'user',
            'active' => $active,
        ];
        $sql = 'UPDATE mail_users SET display_name = :name, role = :role, is_active = :active';
        if (!empty($data['password'])) {
            if (strlen((string) $data['password']) < 12) {
                throw new RuntimeException('Password must be at least 12 characters.');
            }
            $sql .= ', password_hash = :hash, must_change_password = 1';
            $params['hash'] = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id = :id';
        Database::execute($sql, $params);
    }

    /** @param array<string, mixed> $user
     *  @return array<string, int>
     */
    public function folderCounts(array $user): array
    {
        $uid = (int) $user['id'];
        $counts = [
            'inbox_unread' => 0,
            'starred' => 0,
            'drafts' => 0,
            'scheduled' => 0,
        ];
        $rows = Database::fetchAll(
            "SELECT folder, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) AS unread, COUNT(*) AS total
             FROM mail_messages WHERE user_id = :uid AND folder IN ('inbox','drafts','scheduled')
             GROUP BY folder",
            ['uid' => $uid]
        );
        foreach ($rows as $row) {
            if ($row['folder'] === 'inbox') {
                $counts['inbox_unread'] = (int) $row['unread'];
            } elseif ($row['folder'] === 'drafts') {
                $counts['drafts'] = (int) $row['total'];
            } elseif ($row['folder'] === 'scheduled') {
                $counts['scheduled'] = (int) $row['total'];
            }
        }
        $star = Database::fetch(
            "SELECT COUNT(*) AS c FROM mail_messages WHERE user_id = :uid AND is_starred = 1 AND folder != 'trash'",
            ['uid' => $uid]
        );
        $counts['starred'] = (int) ($star['c'] ?? 0);
        return $counts;
    }

    /** @param array<string, mixed> $user
     *  @return array<string, int>
     */
    public function inboxCategoryCounts(array $user): array
    {
        $rows = Database::fetchAll(
            "SELECT category, COUNT(*) AS c FROM mail_messages
             WHERE user_id = :uid AND folder = 'inbox' GROUP BY category",
            ['uid' => (int) $user['id']]
        );
        $out = ['primary' => 0, 'promotions' => 0, 'social' => 0, 'updates' => 0];
        foreach ($rows as $row) {
            $cat = (string) $row['category'];
            if (isset($out[$cat])) {
                $out[$cat] = (int) $row['c'];
            }
        }
        return $out;
    }

    /** @param array<string, mixed> $user
     *  @return array{latest_id:int,unread:int}
     */
    public function inboxState(array $user): array
    {
        $uid = (int) $user['id'];
        $latest = Database::fetch(
            "SELECT MAX(id) AS m FROM mail_messages WHERE user_id = :uid AND folder = 'inbox'",
            ['uid' => $uid]
        );
        $unread = Database::fetch(
            "SELECT COUNT(*) AS c FROM mail_messages WHERE user_id = :uid AND folder = 'inbox' AND is_read = 0",
            ['uid' => $uid]
        );
        return [
            'latest_id' => (int) ($latest['m'] ?? 0),
            'unread' => (int) ($unread['c'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @return array{items:list<array<string,mixed>>,total:int,page:int,pages:int}
     */
    public function listMessages(array $user, string $folder, ?string $category, ?string $q, int $page): array
    {
        $uid = (int) $user['id'];
        $page = max(1, $page);
        $offset = ($page - 1) * self::PER_PAGE;
        $where = ['user_id = :uid'];
        $params = ['uid' => $uid];

        if ($folder === 'starred') {
            $where[] = "is_starred = 1 AND folder != 'trash'";
        } else {
            if (!in_array($folder, self::FOLDERS, true)) {
                $folder = 'inbox';
            }
            $where[] = 'folder = :folder';
            $params['folder'] = $folder;
            if ($folder === 'inbox' && $category !== null && $category !== '' && $q === null) {
                if (in_array($category, ['primary', 'promotions', 'social', 'updates'], true)) {
                    $where[] = 'category = :category';
                    $params['category'] = $category;
                }
            }
        }

        if ($q !== null && trim($q) !== '') {
            $where[] = '(subject LIKE :q OR snippet LIKE :q OR from_email LIKE :q OR from_name LIKE :q OR body_text LIKE :q)';
            $params['q'] = '%' . trim($q) . '%';
        }

        $sqlWhere = implode(' AND ', $where);
        $order = $folder === 'scheduled' ? 'scheduled_at ASC, id ASC' : 'COALESCE(received_at, sent_at, created_at) DESC, id DESC';

        $totalRow = Database::fetch("SELECT COUNT(*) AS c FROM mail_messages WHERE {$sqlWhere}", $params);
        $total = (int) ($totalRow['c'] ?? 0);
        $items = Database::fetchAll(
            "SELECT * FROM mail_messages WHERE {$sqlWhere} ORDER BY {$order} LIMIT " . self::PER_PAGE . " OFFSET {$offset}",
            $params
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / self::PER_PAGE)),
        ];
    }

    /** @param array<string, mixed> $user
     *  @return array<string, mixed>|null
     */
    public function getMessage(array $user, int $id): ?array
    {
        return Database::fetch(
            'SELECT * FROM mail_messages WHERE id = :id AND user_id = :uid LIMIT 1',
            ['id' => $id, 'uid' => (int) $user['id']]
        );
    }

    /** @return list<array<string, mixed>> */
    public function getAttachments(int $messageId): array
    {
        return Database::fetchAll(
            'SELECT * FROM mail_attachments WHERE message_id = :id ORDER BY id ASC',
            ['id' => $messageId]
        );
    }

    /** @param array<string, mixed> $user
     *  @return array<string, mixed>|null
     */
    public function getAttachment(array $user, int $attachmentId): ?array
    {
        return Database::fetch(
            'SELECT a.* FROM mail_attachments a
             INNER JOIN mail_messages m ON m.id = a.message_id
             WHERE a.id = :id AND m.user_id = :uid LIMIT 1',
            ['id' => $attachmentId, 'uid' => (int) $user['id']]
        );
    }

    public function markRead(array $user, int $id, bool $read = true): void
    {
        Database::execute(
            'UPDATE mail_messages SET is_read = :r WHERE id = :id AND user_id = :uid',
            ['r' => $read ? 1 : 0, 'id' => $id, 'uid' => (int) $user['id']]
        );
    }

    public function toggleStar(array $user, int $id): bool
    {
        $msg = $this->getMessage($user, $id);
        if ($msg === null) {
            return false;
        }
        $next = (int) $msg['is_starred'] === 1 ? 0 : 1;
        Database::execute(
            'UPDATE mail_messages SET is_starred = :s WHERE id = :id AND user_id = :uid',
            ['s' => $next, 'id' => $id, 'uid' => (int) $user['id']]
        );
        return (bool) $next;
    }

    /** @param list<int> $ids */
    public function moveMessages(array $user, array $ids, string $folder): void
    {
        if (!in_array($folder, self::FOLDERS, true) || $ids === []) {
            return;
        }
        $uid = (int) $user['id'];
        foreach ($ids as $id) {
            Database::execute(
                'UPDATE mail_messages SET folder = :f WHERE id = :id AND user_id = :uid',
                ['f' => $folder, 'id' => (int) $id, 'uid' => $uid]
            );
        }
    }

    /** @param list<int> $ids */
    public function deleteMessages(array $user, array $ids): void
    {
        $uid = (int) $user['id'];
        foreach ($ids as $id) {
            $atts = $this->getAttachments((int) $id);
            foreach ($atts as $att) {
                $path = Config::root() . '/' . ltrim((string) $att['storage_path'], '/');
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            Database::execute(
                'DELETE FROM mail_messages WHERE id = :id AND user_id = :uid',
                ['id' => (int) $id, 'uid' => $uid]
            );
        }
    }

    /** @param list<int> $ids */
    public function bulkSetRead(array $user, array $ids, bool $read): void
    {
        foreach ($ids as $id) {
            $this->markRead($user, (int) $id, $read);
        }
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $files $_FILES['attachments'] shape optional
     * @return array{ok:bool,error?:string}
     */
    public function validateUploads(array $files): array
    {
        $maxFiles = Config::int('MAIL_MAX_ATTACHMENTS', 10);
        $maxBytes = Config::int('MAIL_MAX_ATTACHMENT_BYTES', 10485760);
        if (!isset($files['name']) || !is_array($files['name'])) {
            return ['ok' => true];
        }
        $count = count($files['name']);
        if ($count > $maxFiles) {
            return ['ok' => false, 'error' => 'Too many attachments (max ' . $maxFiles . ').'];
        }
        $total = 0;
        for ($i = 0; $i < $count; $i++) {
            $name = trim((string) ($files['name'][$i] ?? ''));
            $error = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE || ($name === '' && $error !== UPLOAD_ERR_OK)) {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                return ['ok' => false, 'error' => $this->uploadErrorMessage($error, $name)];
            }
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($ext === '' || !in_array($ext, self::ALLOWED_EXT, true)) {
                return ['ok' => false, 'error' => 'File type not allowed' . ($ext !== '' ? ': .' . $ext : '') . '.'];
            }
            $size = (int) ($files['size'][$i] ?? 0);
            if ($size <= 0) {
                return ['ok' => false, 'error' => '"' . $name . '" is empty or could not be read.'];
            }
            if ($size > $maxBytes) {
                return ['ok' => false, 'error' => '"' . $name . '" exceeds the ' . $this->formatBytes($maxBytes) . ' attachment limit.'];
            }
            $total += $size;
        }
        if ($total > $maxBytes) {
            return ['ok' => false, 'error' => 'Attachments together exceed the ' . $this->formatBytes($maxBytes) . ' limit.'];
        }
        return ['ok' => true];
    }

    private function uploadErrorMessage(int $code, string $name): string
    {
        $label = $name !== '' ? '"' . $name . '"' : 'Attachment';
        $phpLimit = $this->parseIniBytes((string) ini_get('upload_max_filesize'));
        $appLimit = Config::int('MAIL_MAX_ATTACHMENT_BYTES', 10485760);
        $cap = $phpLimit > 0 ? min($phpLimit, $appLimit) : $appLimit;
        $limit = $this->formatBytes($cap);

        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => $label . ' is too large. Max per file is about ' . $limit . '.',
            UPLOAD_ERR_PARTIAL => $label . ' upload was incomplete. Try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temp folder is missing. Contact hosting support.',
            UPLOAD_ERR_CANT_WRITE => 'Server could not save ' . $label . '. Check disk space and permissions.',
            UPLOAD_ERR_EXTENSION => 'A server extension blocked ' . $label . '.',
            default => 'Could not upload ' . $label . ' (error ' . $code . ').',
        };
    }

    private function parseIniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        if (ctype_digit($value)) {
            return (int) $value;
        }
        $unit = strtolower(substr($value, -1));
        $num = (float) substr($value, 0, -1);
        return (int) match ($unit) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => (int) $value,
        };
    }

    /** @return array{per_file:int,total:int,php_upload:int,php_post:int} */
    public function uploadLimits(): array
    {
        $app = Config::int('MAIL_MAX_ATTACHMENT_BYTES', 10485760);
        $phpUpload = $this->parseIniBytes((string) ini_get('upload_max_filesize'));
        $phpPost = $this->parseIniBytes((string) ini_get('post_max_size'));
        $caps = array_values(array_filter([$app, $phpUpload, $phpPost], static fn (int $n): bool => $n > 0));
        $effective = $caps !== [] ? min($caps) : $app;
        // Leave headroom in the multipart POST for fields/HTML body.
        $total = $phpPost > 0 ? max(1024 * 1024, (int) floor($phpPost * 0.85)) : $app;
        $total = min($total, $app);
        return [
            'per_file' => min($effective, $app),
            'total' => $total,
            'php_upload' => $phpUpload,
            'php_post' => $phpPost,
        ];
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return rtrim(rtrim(number_format($bytes / 1048576, 1), '0'), '.') . ' MB';
        }
        if ($bytes >= 1024) {
            return (string) (int) round($bytes / 1024) . ' KB';
        }
        return $bytes . ' B';
    }

    /** Short display label for a recipient (first name or local-part). */
    public function recipientShortLabel(string $raw): string
    {
        $part = trim($raw);
        if ($part === '') {
            return '';
        }
        if (preg_match('/^(.+?)\s*<([^>]+)>$/', $part, $m)) {
            $name = trim($m[1], " \t\"'");
            $bits = preg_split('/\s+/', $name) ?: [];
            if (($bits[0] ?? '') !== '') {
                return (string) $bits[0];
            }
            $local = explode('@', strtolower($m[2]))[0] ?? $m[2];
            $localBits = preg_split('/[._+-]/', $local) ?: [];
            return (string) ($localBits[0] ?? $local);
        }
        if (str_contains($part, '@')) {
            $local = explode('@', $part)[0];
            $localBits = preg_split('/[._+-]/', $local) ?: [];
            return (string) ($localBits[0] ?? $local);
        }
        $bits = preg_split('/\s+/', $part) ?: [];
        return (string) ($bits[0] ?? $part);
    }

    /**
     * @param list<string> $recipients
     * @return array{visible:list<string>,more:int}
     */
    public function summarizeRecipients(array $recipients, int $maxChars = 36): array
    {
        $labels = [];
        foreach ($recipients as $r) {
            $label = $this->recipientShortLabel((string) $r);
            if ($label !== '') {
                $labels[] = $label;
            }
        }
        if ($labels === []) {
            return ['visible' => [], 'more' => 0];
        }
        $visibleCount = 1;
        if (count($labels) >= 2) {
            $two = $labels[0] . ', ' . $labels[1];
            $visibleCount = mb_strlen($two) <= $maxChars ? 2 : 1;
        }
        $visible = array_slice($labels, 0, $visibleCount);
        return ['visible' => $visible, 'more' => count($labels) - count($visible)];
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @param array<string, mixed> $files
     * @return array{ok:bool,id?:int,error?:string}
     */
    public function sendMessage(array $user, array $input, array $files = []): array
    {
        $check = $this->validateUploads($files['attachments'] ?? []);
        if (!$check['ok']) {
            return $check;
        }

        $to = $this->parseAddressList((string) ($input['to'] ?? ''));
        $cc = $this->parseAddressList((string) ($input['cc'] ?? ''));
        $bcc = $this->parseAddressList((string) ($input['bcc'] ?? ''));
        if ($to === []) {
            return ['ok' => false, 'error' => 'Add at least one recipient.'];
        }

        $html = MailHtml::sanitize((string) ($input['html'] ?? ''));
        $html = $this->materializeInlineImages($user, $html);
        $text = MailHtml::toText($html);
        $subject = trim((string) ($input['subject'] ?? ''));
        $scheduledRaw = trim((string) ($input['scheduled_at'] ?? ''));
        $scheduledAt = null;
        $folder = 'sent';

        if ($scheduledRaw !== '') {
            try {
                $scheduledAt = $this->parseScheduleLocal($scheduledRaw);
                $folder = 'scheduled';
            } catch (RuntimeException $e) {
                return ['ok' => false, 'error' => $e->getMessage()];
            }
        }

        $draftId = isset($input['draft_id']) && $input['draft_id'] !== '' ? (int) $input['draft_id'] : null;
        $fromEmail = (string) $user['email'];
        $fromName = (string) $user['display_name'];
        $messageId = '<' . bin2hex(random_bytes(12)) . '@' . $this->domain() . '>';

        $payload = [
            'from' => $fromName . ' <' . $fromEmail . '>',
            'to' => $to,
            'subject' => $subject !== '' ? $subject : '(no subject)',
            'html' => $html,
            'text' => $text,
        ];
        if ($cc !== []) {
            $payload['cc'] = $cc;
        }
        if ($bcc !== []) {
            $payload['bcc'] = $bcc;
        }
        if (!empty($input['in_reply_to'])) {
            $payload['headers'] = ['In-Reply-To' => (string) $input['in_reply_to']];
        }
        if ($scheduledAt !== null) {
            $payload['scheduled_at'] = $scheduledAt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
        }

        // Persist message first to get id for attachments
        if ($draftId) {
            $existing = $this->getMessage($user, $draftId);
            if ($existing === null || $existing['folder'] !== 'drafts') {
                $draftId = null;
            }
        }

        $row = [
            'user_id' => (int) $user['id'],
            'folder' => $folder,
            'category' => 'primary',
            'direction' => 'outbound',
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'to_emails' => json_encode($to, JSON_THROW_ON_ERROR),
            'cc_emails' => json_encode($cc, JSON_THROW_ON_ERROR),
            'bcc_emails' => json_encode($bcc, JSON_THROW_ON_ERROR),
            'subject' => $subject,
            'body_html' => $html,
            'body_text' => $text,
            'snippet' => $this->makeSnippet($text),
            'is_read' => 1,
            'message_id' => $messageId,
            'in_reply_to' => ($input['in_reply_to'] ?? null) ?: null,
            'sent_at' => $scheduledAt ? null : date('Y-m-d H:i:s'),
            'scheduled_at' => $scheduledAt?->format('Y-m-d H:i:s'),
        ];

        if ($draftId) {
            Database::execute(
                'UPDATE mail_messages SET folder=:folder, to_emails=:to_emails, cc_emails=:cc_emails, bcc_emails=:bcc_emails,
                 subject=:subject, body_html=:body_html, body_text=:body_text, snippet=:snippet, message_id=:message_id,
                 in_reply_to=:in_reply_to, sent_at=:sent_at, scheduled_at=:scheduled_at, direction=:direction, is_read=1
                 WHERE id=:id AND user_id=:user_id',
                [
                    'folder' => $row['folder'],
                    'to_emails' => $row['to_emails'],
                    'cc_emails' => $row['cc_emails'],
                    'bcc_emails' => $row['bcc_emails'],
                    'subject' => $row['subject'],
                    'body_html' => $row['body_html'],
                    'body_text' => $row['body_text'],
                    'snippet' => $row['snippet'],
                    'message_id' => $row['message_id'],
                    'in_reply_to' => $row['in_reply_to'],
                    'sent_at' => $row['sent_at'],
                    'scheduled_at' => $row['scheduled_at'],
                    'direction' => $row['direction'],
                    'id' => $draftId,
                    'user_id' => $row['user_id'],
                ]
            );
            $msgId = $draftId;
        } else {
            Database::execute(
                'INSERT INTO mail_messages
                (user_id, folder, category, direction, from_email, from_name, to_emails, cc_emails, bcc_emails,
                 subject, body_html, body_text, snippet, is_read, message_id, in_reply_to, sent_at, scheduled_at)
                 VALUES
                (:user_id, :folder, :category, :direction, :from_email, :from_name, :to_emails, :cc_emails, :bcc_emails,
                 :subject, :body_html, :body_text, :snippet, :is_read, :message_id, :in_reply_to, :sent_at, :scheduled_at)',
                $row
            );
            $msgId = (int) Database::lastId();
        }

        $attachmentsPayload = $this->storeUploadedAttachments($msgId, $files['attachments'] ?? [], 'outbound');
        $stagedTokens = $input['staged'] ?? [];
        if (!is_array($stagedTokens)) {
            $stagedTokens = $stagedTokens !== '' && $stagedTokens !== null ? [(string) $stagedTokens] : [];
        }
        $attachmentsPayload = array_merge(
            $attachmentsPayload,
            $this->consumeStagedAttachments((int) $user['id'], $msgId, array_map('strval', $stagedTokens), 'outbound')
        );
        if ($attachmentsPayload !== []) {
            $payload['attachments'] = $attachmentsPayload;
        }

        $result = $this->resend->sendEmail($payload);
        if (!$result['ok']) {
            // Keep as draft for retry
            Database::execute(
                "UPDATE mail_messages SET folder='drafts', scheduled_at=NULL, sent_at=NULL WHERE id=:id",
                ['id' => $msgId]
            );
            MailSession::set('mail_compose_retry', [
                'to' => implode(', ', $to),
                'cc' => implode(', ', $cc),
                'bcc' => implode(', ', $bcc),
                'subject' => $subject,
                'html' => $html,
                'draft_id' => $msgId,
            ]);
            return ['ok' => false, 'error' => $result['error'] ?? 'Send failed', 'id' => $msgId];
        }

        Database::execute(
            'UPDATE mail_messages SET resend_email_id = :rid WHERE id = :id',
            ['rid' => $result['id'] ?? null, 'id' => $msgId]
        );

        return ['ok' => true, 'id' => $msgId];
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @param array<string, mixed> $files
     * @return array{ok:bool,id?:int,error?:string}
     */
    public function saveDraft(array $user, array $input, array $files = []): array
    {
        $check = $this->validateUploads($files['attachments'] ?? []);
        if (!$check['ok']) {
            return $check;
        }

        $to = $this->parseAddressList((string) ($input['to'] ?? ''));
        $cc = $this->parseAddressList((string) ($input['cc'] ?? ''));
        $bcc = $this->parseAddressList((string) ($input['bcc'] ?? ''));
        $html = MailHtml::sanitize((string) ($input['html'] ?? ''));
        $text = MailHtml::toText($html);
        $subject = trim((string) ($input['subject'] ?? ''));
        $draftId = isset($input['draft_id']) && $input['draft_id'] !== '' ? (int) $input['draft_id'] : null;

        $row = [
            'user_id' => (int) $user['id'],
            'folder' => 'drafts',
            'category' => 'primary',
            'direction' => 'outbound',
            'from_email' => (string) $user['email'],
            'from_name' => (string) $user['display_name'],
            'to_emails' => json_encode($to, JSON_THROW_ON_ERROR),
            'cc_emails' => json_encode($cc, JSON_THROW_ON_ERROR),
            'bcc_emails' => json_encode($bcc, JSON_THROW_ON_ERROR),
            'subject' => $subject,
            'body_html' => $html,
            'body_text' => $text,
            'snippet' => $this->makeSnippet($text),
            'is_read' => 1,
            'in_reply_to' => ($input['in_reply_to'] ?? null) ?: null,
        ];

        if ($draftId && $this->getMessage($user, $draftId)) {
            Database::execute(
                'UPDATE mail_messages SET to_emails=:to_emails, cc_emails=:cc_emails, bcc_emails=:bcc_emails,
                 subject=:subject, body_html=:body_html, body_text=:body_text, snippet=:snippet, in_reply_to=:in_reply_to, folder=:folder
                 WHERE id=:id AND user_id=:user_id',
                [
                    'to_emails' => $row['to_emails'],
                    'cc_emails' => $row['cc_emails'],
                    'bcc_emails' => $row['bcc_emails'],
                    'subject' => $row['subject'],
                    'body_html' => $row['body_html'],
                    'body_text' => $row['body_text'],
                    'snippet' => $row['snippet'],
                    'in_reply_to' => $row['in_reply_to'],
                    'folder' => $row['folder'],
                    'id' => $draftId,
                    'user_id' => $row['user_id'],
                ]
            );
            $msgId = $draftId;
        } else {
            Database::execute(
                'INSERT INTO mail_messages
                (user_id, folder, category, direction, from_email, from_name, to_emails, cc_emails, bcc_emails,
                 subject, body_html, body_text, snippet, is_read, in_reply_to)
                 VALUES
                (:user_id, :folder, :category, :direction, :from_email, :from_name, :to_emails, :cc_emails, :bcc_emails,
                 :subject, :body_html, :body_text, :snippet, :is_read, :in_reply_to)',
                $row
            );
            $msgId = (int) Database::lastId();
        }

        $this->storeUploadedAttachments($msgId, $files['attachments'] ?? [], 'drafts');
        $stagedTokens = $input['staged'] ?? [];
        if (!is_array($stagedTokens)) {
            $stagedTokens = $stagedTokens !== '' && $stagedTokens !== null ? [(string) $stagedTokens] : [];
        }
        $this->consumeStagedAttachments((int) $user['id'], $msgId, array_map('strval', $stagedTokens), 'drafts');
        return ['ok' => true, 'id' => $msgId];
    }

    public function cancelScheduled(array $user, int $id): array
    {
        $msg = $this->getMessage($user, $id);
        if ($msg === null || $msg['folder'] !== 'scheduled') {
            return ['ok' => false, 'error' => 'Not found'];
        }
        $rid = (string) ($msg['resend_email_id'] ?? '');
        if ($rid !== '') {
            $this->resend->cancelEmail($rid);
        }
        Database::execute(
            "UPDATE mail_messages SET folder='drafts', scheduled_at=NULL, resend_email_id=NULL WHERE id=:id AND user_id=:uid",
            ['id' => $id, 'uid' => (int) $user['id']]
        );
        return ['ok' => true];
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $get
     * @return array<string, mixed>
     */
    public function composeContext(array $user, array $get): array
    {
        $mode = (string) ($get['compose'] ?? '');
        $retry = MailSession::get('mail_compose_retry');
        if (is_array($retry) && $mode === '') {
            MailSession::forget('mail_compose_retry');
            return [
                'open' => true,
                'title' => 'New message',
                'mode' => 'new',
                'to' => (string) ($retry['to'] ?? ''),
                'cc' => (string) ($retry['cc'] ?? ''),
                'bcc' => (string) ($retry['bcc'] ?? ''),
                'subject' => (string) ($retry['subject'] ?? ''),
                'html' => (string) ($retry['html'] ?? ''),
                'draft_id' => $retry['draft_id'] ?? '',
                'in_reply_to' => (string) ($retry['in_reply_to'] ?? ''),
            ];
        }

        if ($mode === '') {
            return ['open' => false];
        }

        $ctx = [
            'open' => true,
            'title' => 'New message',
            'mode' => $mode,
            'to' => '',
            'cc' => '',
            'bcc' => '',
            'subject' => '',
            'html' => '',
            'draft_id' => '',
            'in_reply_to' => '',
        ];

        if ($mode === 'new') {
            $sig = $this->defaultSignature($user);
            if ($sig && (int) $user['signature_on_new'] === 1) {
                $ctx['html'] = $this->signatureBlock((string) $sig['body_html']);
            }
            return $ctx;
        }

        if ($mode === 'draft') {
            $draftId = (int) ($get['draft'] ?? 0);
            $msg = $this->getMessage($user, $draftId);
            if ($msg && $msg['folder'] === 'drafts') {
                $ctx['title'] = 'Edit draft';
                $ctx['to'] = implode(', ', $this->decodeJsonList($msg['to_emails'] ?? '[]'));
                $ctx['cc'] = implode(', ', $this->decodeJsonList($msg['cc_emails'] ?? '[]'));
                $ctx['bcc'] = implode(', ', $this->decodeJsonList($msg['bcc_emails'] ?? '[]'));
                $ctx['subject'] = (string) $msg['subject'];
                $ctx['html'] = (string) ($msg['body_html'] ?? '');
                $ctx['draft_id'] = (string) $msg['id'];
                $ctx['in_reply_to'] = (string) ($msg['in_reply_to'] ?? '');
            }
            return $ctx;
        }

        $sourceId = (int) ($get['source'] ?? 0);
        $src = $this->getMessage($user, $sourceId);
        if ($src === null) {
            return $ctx;
        }

        $quote = $this->quoteBlock($src);
        $sigHtml = '';
        if ((int) $user['signature_on_reply'] === 1) {
            $sig = $this->defaultSignature($user);
            if ($sig) {
                $sigHtml = $this->signatureBlock((string) $sig['body_html']);
            }
        }

        if ($mode === 'reply' || $mode === 'reply-all') {
            $ctx['title'] = $mode === 'reply-all' ? 'Reply all' : 'Reply';
            $ctx['to'] = (string) $src['from_email'];
            $ctx['subject'] = $this->prefixSubject((string) $src['subject'], 'Re:');
            $ctx['in_reply_to'] = (string) ($src['message_id'] ?? '');
            if ($mode === 'reply-all') {
                $others = array_merge(
                    $this->decodeJsonList($src['to_emails'] ?? '[]'),
                    $this->decodeJsonList($src['cc_emails'] ?? '[]')
                );
                $me = strtolower((string) $user['email']);
                $cc = [];
                foreach ($others as $addr) {
                    if (strtolower($addr) !== $me && strtolower($addr) !== strtolower((string) $src['from_email'])) {
                        $cc[] = $addr;
                    }
                }
                $ctx['cc'] = implode(', ', array_unique($cc));
            }
            $ctx['html'] = '<div><br></div>' . $sigHtml . $quote;
            return $ctx;
        }

        if ($mode === 'forward') {
            $ctx['title'] = 'Forward';
            $ctx['subject'] = $this->prefixSubject((string) $src['subject'], 'Fwd:');
            $ctx['html'] = '<div><br></div>' . $sigHtml . $quote;
        }

        return $ctx;
    }

    /** @return list<array{label:string,value:string,day:string,time:string,period:string}> */
    public function scheduleOptions(): array
    {
        $tz = new DateTimeZone('Africa/Lagos');
        $now = new DateTimeImmutable('now', $tz);
        $tomorrow = $now->modify('+1 day');
        $nextMon = $now->modify('next monday');
        $fmt = static fn (DateTimeImmutable $d): string => $d->format('Y-m-d H:i');
        $day = static fn (DateTimeImmutable $d): string => $d->format('D j M');

        $morning = $tomorrow->setTime(8, 0);
        $afternoon = $tomorrow->setTime(13, 0);
        $monday = $nextMon->setTime(8, 0);

        return [
            [
                'label' => 'Tomorrow morning (8:00)',
                'value' => $fmt($morning),
                'day' => $day($morning),
                'time' => '08:00',
                'period' => 'Morning',
            ],
            [
                'label' => 'Tomorrow afternoon (13:00)',
                'value' => $fmt($afternoon),
                'day' => $day($afternoon),
                'time' => '13:00',
                'period' => 'Afternoon',
            ],
            [
                'label' => 'Next Monday morning (8:00)',
                'value' => $fmt($monday),
                'day' => $day($monday),
                'time' => '08:00',
                'period' => 'Monday',
            ],
        ];
    }

    public function timezoneLabel(): string
    {
        return 'Africa/Lagos (WAT)';
    }

    public function formatScheduledAt(?string $dt): string
    {
        if ($dt === null || $dt === '') {
            return '';
        }
        try {
            $d = new DateTimeImmutable($dt, new DateTimeZone('Africa/Lagos'));
            return $d->format('D, j M Y · H:i');
        } catch (\Throwable) {
            return $dt;
        }
    }

    public function processInboundWebhook(string $payload, array $headers): array
    {
        if (!$this->resend->verifyWebhook($payload, $headers)) {
            return ['ok' => false, 'error' => 'Invalid signature'];
        }
        /** @var array<string, mixed>|null $data */
        $data = json_decode($payload, true);
        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'Bad JSON'];
        }
        $type = (string) ($data['type'] ?? '');
        if ($type !== 'email.received') {
            return ['ok' => true, 'ignored' => true];
        }
        $emailId = (string) (($data['data']['email_id'] ?? $data['data']['id'] ?? '') ?: '');
        if ($emailId === '') {
            return ['ok' => false, 'error' => 'Missing email id'];
        }
        return $this->importReceivedEmail($emailId);
    }

    public function importReceivedEmail(string $resendEmailId): array
    {
        $existing = Database::fetch(
            'SELECT id FROM mail_messages WHERE resend_email_id = :id LIMIT 1',
            ['id' => $resendEmailId]
        );
        if ($existing) {
            return ['ok' => true, 'duplicate' => true, 'id' => (int) $existing['id']];
        }

        $fetched = $this->resend->getReceivedEmail($resendEmailId);
        if (!$fetched['ok'] || empty($fetched['data'])) {
            return ['ok' => false, 'error' => $fetched['error'] ?? 'Fetch failed'];
        }
        $data = $fetched['data'];

        $toList = [];
        $rawTo = $data['to'] ?? [];
        if (is_string($rawTo)) {
            $toList = $this->parseAddressList($rawTo);
        } elseif (is_array($rawTo)) {
            foreach ($rawTo as $t) {
                if (is_string($t)) {
                    $toList[] = strtolower($t);
                } elseif (is_array($t) && isset($t['email'])) {
                    $toList[] = strtolower((string) $t['email']);
                }
            }
        }

        $user = null;
        foreach ($toList as $addr) {
            $user = $this->findUserByEmail($addr);
            if ($user && (int) $user['is_active']) {
                break;
            }
            $user = null;
        }
        if ($user === null) {
            $catch = (string) Config::get('MAIL_CATCH_ALL_EMAIL', '');
            if ($catch !== '') {
                $user = $this->findUserByEmail($catch);
            }
        }
        if ($user === null) {
            return ['ok' => false, 'error' => 'No mailbox for recipients'];
        }

        $fromEmail = '';
        $fromName = '';
        $from = $data['from'] ?? '';
        if (is_string($from) && preg_match('/^(.*?)<([^>]+)>$/', $from, $m)) {
            $fromName = trim($m[1], " \t\"'");
            $fromEmail = strtolower(trim($m[2]));
        } elseif (is_string($from)) {
            $fromEmail = strtolower(trim($from));
        } elseif (is_array($from)) {
            $fromEmail = strtolower((string) ($from['email'] ?? ''));
            $fromName = (string) ($from['name'] ?? '');
        }

        $subject = (string) ($data['subject'] ?? '');
        $html = MailHtml::sanitize((string) ($data['html'] ?? $data['body'] ?? ''));
        $text = (string) ($data['text'] ?? MailHtml::toText($html));
        $headers = is_array($data['headers'] ?? null) ? $data['headers'] : [];
        $category = $this->categorizeInbound($fromEmail, $subject, $headers);

        Database::execute(
            'INSERT INTO mail_messages
            (user_id, folder, category, direction, from_email, from_name, to_emails, cc_emails, bcc_emails,
             subject, body_html, body_text, snippet, is_read, message_id, resend_email_id, received_at)
             VALUES
            (:user_id, \'inbox\', :category, \'inbound\', :from_email, :from_name, :to_emails, :cc_emails, :bcc_emails,
             :subject, :body_html, :body_text, :snippet, 0, :message_id, :resend_email_id, NOW())',
            [
                'user_id' => (int) $user['id'],
                'category' => $category,
                'from_email' => $fromEmail,
                'from_name' => $fromName !== '' ? $fromName : null,
                'to_emails' => json_encode($toList, JSON_THROW_ON_ERROR),
                'cc_emails' => json_encode([], JSON_THROW_ON_ERROR),
                'bcc_emails' => json_encode([], JSON_THROW_ON_ERROR),
                'subject' => $subject,
                'body_html' => $html,
                'body_text' => $text,
                'snippet' => $this->makeSnippet($text),
                'message_id' => (string) ($data['message_id'] ?? $headers['message-id'] ?? ''),
                'resend_email_id' => $resendEmailId,
            ]
        );
        $msgId = (int) Database::lastId();

        $atts = $data['attachments'] ?? [];
        if (is_array($atts)) {
            foreach ($atts as $att) {
                if (!is_array($att)) {
                    continue;
                }
                $attId = (string) ($att['id'] ?? '');
                $filename = (string) ($att['filename'] ?? 'attachment');
                $contentType = (string) ($att['content_type'] ?? 'application/octet-stream');
                $bin = '';
                if ($attId !== '') {
                    $dl = $this->resend->downloadReceivedAttachment($resendEmailId, $attId);
                    if ($dl['ok']) {
                        $bin = (string) ($dl['content'] ?? '');
                    }
                }
                if ($bin === '' && isset($att['content'])) {
                    $bin = base64_decode((string) $att['content'], true) ?: '';
                }
                if ($bin === '') {
                    continue;
                }
                $this->storeAttachmentFile($msgId, $filename, $contentType, $bin, 'inbound', $attId !== '' ? $attId : null);
            }
        }

        return ['ok' => true, 'id' => $msgId];
    }

    public function syncInboundFromResend(int $limit = 30): array
    {
        $list = $this->resend->listReceivedEmails($limit);
        if (!$list['ok']) {
            return $list;
        }
        $imported = 0;
        foreach ($list['data'] ?? [] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = (string) ($item['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $res = $this->importReceivedEmail($id);
            if (!empty($res['ok']) && empty($res['duplicate'])) {
                $imported++;
            }
        }
        return ['ok' => true, 'imported' => $imported];
    }

    /** @param array<string, mixed> $headers */
    public function categorizeInbound(string $from, string $subject, array $headers): string
    {
        $fromDomain = strtolower((string) (substr(strrchr($from, '@') ?: '', 1)));
        foreach (self::SOCIAL_DOMAINS as $d) {
            if ($fromDomain === $d || str_ends_with($fromDomain, '.' . $d)) {
                return 'social';
            }
        }

        $local = strtolower((string) strstr($from, '@', true));
        $autoLocals = ['noreply', 'no-reply', 'notifications', 'billing', 'mailer-daemon', 'donotreply'];
        foreach ($autoLocals as $a) {
            if ($local === $a || str_starts_with($local, $a . '+') || str_contains($local, $a)) {
                return 'updates';
            }
        }

        $auto = strtolower((string) ($headers['auto-submitted'] ?? $headers['Auto-Submitted'] ?? ''));
        if (str_contains($auto, 'auto')) {
            return 'updates';
        }

        $subj = strtolower($subject);
        foreach (['receipt', 'invoice', 'order confirmation', 'shipping', 'password reset', 'verify your', 'security alert'] as $p) {
            if (str_contains($subj, $p)) {
                return 'updates';
            }
        }

        $listUnsub = $headers['list-unsubscribe'] ?? $headers['List-Unsubscribe'] ?? null;
        $listId = $headers['list-id'] ?? $headers['List-Id'] ?? null;
        $prec = strtolower((string) ($headers['precedence'] ?? $headers['Precedence'] ?? ''));
        if ($listUnsub || $listId || in_array($prec, ['bulk', 'list', 'junk'], true)) {
            return 'promotions';
        }
        foreach (['newsletter', 'marketing', 'promo', 'deals'] as $m) {
            if (str_contains($local, $m)) {
                return 'promotions';
            }
        }

        return 'primary';
    }

    /** @param array<string, mixed> $user */
    public function storeInlineImage(array $user, array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload failed'];
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            return ['ok' => false, 'error' => 'Invalid image'];
        }
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            default => 'webp',
        };
        $uid = (int) $user['id'];
        $relDir = 'uploads/mail-inline/' . $uid;
        $publicDir = Config::get('PUBLIC_PATH') . '/' . $relDir;
        $storageDir = Config::root() . '/storage/' . $relDir;
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        $name = bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = $publicDir . '/' . $name;
        if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
            return ['ok' => false, 'error' => 'Could not save'];
        }
        @copy($dest, $storageDir . '/' . $name);
        $url = rtrim((string) Config::get('APP_URL'), '/') . '/' . $relDir . '/' . $name;
        return ['ok' => true, 'url' => $url];
    }

    /** @param array<string, mixed> $user */
    public function materializeInlineImages(array $user, string $html): string
    {
        return preg_replace_callback(
            '/<img([^>]+)src=["\'](data:image\/[a-zA-Z+]+;base64,[^"\']+)["\']/i',
            function (array $m) use ($user): string {
                $data = $m[2];
                if (!preg_match('#^data:image/(png|jpeg|jpg|gif|webp);base64,(.+)$#i', $data, $dm)) {
                    return $m[0];
                }
                $bin = base64_decode($dm[2], true);
                if ($bin === false) {
                    return $m[0];
                }
                $ext = strtolower($dm[1]) === 'jpeg' ? 'jpg' : strtolower($dm[1]);
                $uid = (int) $user['id'];
                $relDir = 'uploads/mail-inline/' . $uid;
                $publicDir = Config::get('PUBLIC_PATH') . '/' . $relDir;
                if (!is_dir($publicDir)) {
                    mkdir($publicDir, 0755, true);
                }
                $name = bin2hex(random_bytes(8)) . '.' . $ext;
                file_put_contents($publicDir . '/' . $name, $bin);
                $url = rtrim((string) Config::get('APP_URL'), '/') . '/' . $relDir . '/' . $name;
                return '<img' . $m[1] . 'src="' . htmlspecialchars($url, ENT_QUOTES) . '"';
            },
            $html
        ) ?? $html;
    }

    /** @param array<string, mixed> $user */
    public function storeAvatar(array $user, array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload failed'];
        }
        if ((int) ($file['size'] ?? 0) > 3 * 1024 * 1024) {
            return ['ok' => false, 'error' => 'Max 3 MB'];
        }
        if (!extension_loaded('gd')) {
            return ['ok' => false, 'error' => 'GD required'];
        }
        $src = $this->gdLoad((string) $file['tmp_name']);
        if ($src === null) {
            return ['ok' => false, 'error' => 'Invalid image'];
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $side = min($w, $h);
        $x = (int) (($w - $side) / 2);
        $y = (int) (($h - $side) / 2);
        $size = min(256, $side);
        $dst = imagecreatetruecolor($size, $size);
        imagecopyresampled($dst, $src, 0, 0, $x, $y, $size, $size, $side, $side);
        imagedestroy($src);

        $uid = (int) $user['id'];
        $rel = 'uploads/mail-avatars/' . $uid;
        $pub = Config::get('PUBLIC_PATH') . '/' . $rel;
        $stor = Config::root() . '/storage/' . $rel;
        if (!is_dir($pub)) {
            mkdir($pub, 0755, true);
        }
        if (!is_dir($stor)) {
            mkdir($stor, 0755, true);
        }
        $name = 'avatar.png';
        imagepng($dst, $pub . '/' . $name);
        @copy($pub . '/' . $name, $stor . '/' . $name);
        imagedestroy($dst);

        $path = $rel . '/' . $name;
        Database::execute('UPDATE mail_users SET avatar_path = :p WHERE id = :id', ['p' => $path, 'id' => $uid]);
        return ['ok' => true, 'path' => $path];
    }

    public function removeAvatar(array $user): void
    {
        Database::execute('UPDATE mail_users SET avatar_path = NULL WHERE id = :id', ['id' => (int) $user['id']]);
    }

    /** @param array<string, mixed> $user */
    public function avatarUrl(array $user): ?string
    {
        $path = (string) ($user['avatar_path'] ?? '');
        if ($path === '') {
            return null;
        }
        return rtrim((string) Config::get('APP_URL'), '/') . '/' . ltrim($path, '/');
    }

    public function avatarUrlForEmail(string $email): ?string
    {
        $user = $this->findUserByEmail($email);
        return $user ? $this->avatarUrl($user) : null;
    }

    public function htmlSandboxSrcdoc(string $html): string
    {
        $safe = MailHtml::sanitize($html);
        $csp = "default-src 'none'; img-src https: data: cid:; style-src 'unsafe-inline'; font-src https: data:; base-uri 'none'; form-action 'none'; frame-ancestors 'none';";
        $doc = '<!DOCTYPE html><html><head><meta charset="utf-8">'
            . '<meta http-equiv="Content-Security-Policy" content="' . htmlspecialchars($csp, ENT_QUOTES) . '">'
            . '<style>body{margin:0;padding:12px;font:14px/1.5 Manrope,system-ui,sans-serif;color:#222;word-wrap:break-word}img{max-width:100%;height:auto}</style>'
            . '</head><body>' . $safe . '</body></html>';
        return $doc;
    }

    /** @return list<array<string, mixed>> */
    public function listSignatures(array $user): array
    {
        return Database::fetchAll(
            'SELECT * FROM mail_signatures WHERE user_id = :uid ORDER BY is_default DESC, name ASC',
            ['uid' => (int) $user['id']]
        );
    }

    public function getSignature(array $user, int $id): ?array
    {
        return Database::fetch(
            'SELECT * FROM mail_signatures WHERE id = :id AND user_id = :uid LIMIT 1',
            ['id' => $id, 'uid' => (int) $user['id']]
        );
    }

    public function saveSignature(array $user, ?int $id, string $name, string $bodyHtml, bool $isDefault): int
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 120) {
            throw new RuntimeException('Signature name required (≤120).');
        }
        $body = MailHtml::sanitize($bodyHtml);
        $uid = (int) $user['id'];
        if ($isDefault) {
            Database::execute('UPDATE mail_signatures SET is_default = 0 WHERE user_id = :uid', ['uid' => $uid]);
        }
        if ($id) {
            Database::execute(
                'UPDATE mail_signatures SET name=:name, body_html=:body, is_default=:def WHERE id=:id AND user_id=:uid',
                ['name' => $name, 'body' => $body, 'def' => $isDefault ? 1 : 0, 'id' => $id, 'uid' => $uid]
            );
            return $id;
        }
        Database::execute(
            'INSERT INTO mail_signatures (user_id, name, body_html, is_default) VALUES (:uid, :name, :body, :def)',
            ['uid' => $uid, 'name' => $name, 'body' => $body, 'def' => $isDefault ? 1 : 0]
        );
        return (int) Database::lastId();
    }

    public function deleteSignature(array $user, int $id): void
    {
        Database::execute(
            'DELETE FROM mail_signatures WHERE id = :id AND user_id = :uid',
            ['id' => $id, 'uid' => (int) $user['id']]
        );
    }

    public function setDefaultSignature(array $user, int $id): void
    {
        $uid = (int) $user['id'];
        Database::execute('UPDATE mail_signatures SET is_default = 0 WHERE user_id = :uid', ['uid' => $uid]);
        Database::execute(
            'UPDATE mail_signatures SET is_default = 1 WHERE id = :id AND user_id = :uid',
            ['id' => $id, 'uid' => $uid]
        );
    }

    public function defaultSignature(array $user): ?array
    {
        return Database::fetch(
            'SELECT * FROM mail_signatures WHERE user_id = :uid AND is_default = 1 LIMIT 1',
            ['uid' => (int) $user['id']]
        );
    }

    public function updateSignaturePreferences(array $user, bool $onNew, bool $onReply): void
    {
        Database::execute(
            'UPDATE mail_users SET signature_on_new = :n, signature_on_reply = :r WHERE id = :id',
            ['n' => $onNew ? 1 : 0, 'r' => $onReply ? 1 : 0, 'id' => (int) $user['id']]
        );
    }

    public function signatureBlock(string $html): string
    {
        return '<div class="lb-mail-signature">' . MailHtml::sanitize($html) . '</div>';
    }

    /** @return list<string> */
    public function decodeJsonList(mixed $json): array
    {
        if (is_array($json)) {
            return array_values(array_map('strval', $json));
        }
        if (!is_string($json) || $json === '') {
            return [];
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }
        return array_values(array_filter(array_map('strval', $data)));
    }

    public function formatListDate(?string $dt): string
    {
        if ($dt === null || $dt === '') {
            return '';
        }
        try {
            $d = new DateTimeImmutable($dt, new DateTimeZone('Africa/Lagos'));
            $now = new DateTimeImmutable('now', new DateTimeZone('Africa/Lagos'));
            if ($d->format('Y-m-d') === $now->format('Y-m-d')) {
                return $d->format('H:i');
            }
            if ($d->format('Y') === $now->format('Y')) {
                return $d->format('j M');
            }
            return $d->format('j M Y');
        } catch (\Throwable) {
            return '';
        }
    }

    /** @param array<string, mixed> $msg */
    public function displaySender(array $msg): string
    {
        $email = trim((string) ($msg['from_email'] ?? ''));
        $name = trim((string) ($msg['from_name'] ?? ''));
        if ($name !== '' && strcasecmp($name, $email) !== 0) {
            return $name;
        }
        if (($msg['folder'] ?? '') === 'sent' || ($msg['direction'] ?? '') === 'outbound') {
            $to = $this->decodeJsonList($msg['to_emails'] ?? '[]');
            return $to[0] ?? ($email !== '' ? $email : 'Draft');
        }
        return $email;
    }

    public function makeSnippet(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? '';
        return mb_substr($text, 0, 160);
    }

    /**
     * Stage one attachment before send (avoids multipart compose posts that shared hosts often 500).
     *
     * @param array<string, mixed> $user
     * @param array<string, mixed> $file Single $_FILES entry
     * @return array{ok:bool,token?:string,filename?:string,size?:int,error?:string}
     */
    public function stageOutgoingAttachment(array $user, array $file): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $filename = (string) ($file['name'] ?? 'attachment');
        if ($error !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => $this->uploadErrorMessage($error, $filename)];
        }
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, self::ALLOWED_EXT, true)) {
            return ['ok' => false, 'error' => 'File type not allowed' . ($ext !== '' ? ': .' . $ext : '') . '.'];
        }
        $maxBytes = Config::int('MAIL_MAX_ATTACHMENT_BYTES', 10485760);
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            return ['ok' => false, 'error' => '"' . $filename . '" exceeds the ' . $this->formatBytes($maxBytes) . ' limit.'];
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_readable($tmp)) {
            return ['ok' => false, 'error' => 'Could not read uploaded file "' . $filename . '".'];
        }
        $bin = file_get_contents($tmp);
        if ($bin === false || $bin === '') {
            return ['ok' => false, 'error' => 'Could not read uploaded file "' . $filename . '".'];
        }
        $mime = (string) ($file['type'] ?? 'application/octet-stream');
        [$filename, $mime, $bin] = $this->maybeCompressAttachmentBinary($filename, $mime, $bin);

        $uid = (int) $user['id'];
        $token = bin2hex(random_bytes(16));
        $safeExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $leaf = $token . ($safeExt !== '' ? '.' . $safeExt : '');
        $dir = Config::root() . '/storage/mail/staging/' . $uid;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['ok' => false, 'error' => 'Could not create staging folder.'];
        }
        $metaPath = $dir . '/' . $token . '.json';
        $binPath = $dir . '/' . $leaf;
        if (file_put_contents($binPath, $bin) === false) {
            return ['ok' => false, 'error' => 'Could not save staged attachment.'];
        }

        $publicRel = 'uploads/mail-attach/staging/' . $uid . '/' . $leaf;
        $publicFull = rtrim((string) Config::get('PUBLIC_PATH'), '/') . '/' . $publicRel;
        $publicDir = dirname($publicFull);
        if (!is_dir($publicDir) && !mkdir($publicDir, 0755, true) && !is_dir($publicDir)) {
            @unlink($binPath);
            return ['ok' => false, 'error' => 'Could not publish staged attachment.'];
        }
        if (file_put_contents($publicFull, $bin) === false) {
            @unlink($binPath);
            return ['ok' => false, 'error' => 'Could not publish staged attachment.'];
        }

        $meta = [
            'token' => $token,
            'user_id' => $uid,
            'filename' => $filename,
            'content_type' => $mime,
            'size' => strlen($bin),
            'leaf' => $leaf,
            'bin_path' => 'storage/mail/staging/' . $uid . '/' . $leaf,
            'public_rel' => $publicRel,
            'created_at' => time(),
        ];
        file_put_contents($metaPath, json_encode($meta, JSON_THROW_ON_ERROR));

        return [
            'ok' => true,
            'token' => $token,
            'filename' => $filename,
            'size' => strlen($bin),
        ];
    }

    /**
     * @param list<string> $tokens
     * @return list<array{filename:string,path:string}>
     */
    public function consumeStagedAttachments(int $userId, int $messageId, array $tokens, string $kind): array
    {
        $payload = [];
        $tokens = array_values(array_unique(array_filter(array_map('trim', $tokens))));
        foreach ($tokens as $token) {
            if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
                continue;
            }
            $metaPath = Config::root() . '/storage/mail/staging/' . $userId . '/' . $token . '.json';
            if (!is_file($metaPath)) {
                throw new RuntimeException('A staged attachment expired. Re-attach the file and try again.');
            }
            /** @var array<string, mixed>|null $meta */
            $meta = json_decode((string) file_get_contents($metaPath), true);
            if (!is_array($meta) || (int) ($meta['user_id'] ?? 0) !== $userId) {
                throw new RuntimeException('Invalid staged attachment.');
            }
            $binRel = (string) ($meta['bin_path'] ?? '');
            $binFull = Config::root() . '/' . ltrim($binRel, '/');
            $binary = is_file($binFull) ? (string) file_get_contents($binFull) : '';
            if ($binary === '') {
                throw new RuntimeException('Staged attachment data missing. Re-attach and try again.');
            }
            $filename = (string) ($meta['filename'] ?? 'attachment');
            $mime = (string) ($meta['content_type'] ?? 'application/octet-stream');
            $stored = $this->storeAttachmentFile($messageId, $filename, $mime, $binary, $kind);
            $payload[] = [
                'filename' => $filename,
                'path' => $stored['path'],
            ];
            @unlink($metaPath);
            @unlink($binFull);
            $publicFull = rtrim((string) Config::get('PUBLIC_PATH'), '/') . '/' . ltrim((string) ($meta['public_rel'] ?? ''), '/');
            if (is_file($publicFull)) {
                @unlink($publicFull);
            }
        }
        return $payload;
    }

    public function storeAttachmentFile(
        int $messageId,
        string $filename,
        string $contentType,
        string $binary,
        string $kind,
        ?string $resendAttachmentId = null
    ): array {
        $safeExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $rand = bin2hex(random_bytes(16));
        $leaf = $rand . ($safeExt !== '' ? '.' . $safeExt : '');
        $rel = 'storage/mail/' . $kind . '/' . $messageId . '/' . $leaf;
        $full = Config::root() . '/' . $rel;
        $dir = dirname($full);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not create attachment storage folder. Check permissions on storage/mail.');
        }
        if (file_put_contents($full, $binary) === false) {
            throw new RuntimeException('Could not save attachment to disk. Check permissions on storage/mail.');
        }

        // Public copy so Resend can fetch via URL (avoids huge base64 API payloads).
        $publicRel = 'uploads/mail-attach/' . $messageId . '/' . $leaf;
        $publicFull = rtrim((string) Config::get('PUBLIC_PATH'), '/') . '/' . $publicRel;
        $publicDir = dirname($publicFull);
        if (!is_dir($publicDir) && !mkdir($publicDir, 0755, true) && !is_dir($publicDir)) {
            throw new RuntimeException('Could not create public attachment folder. Check permissions on public/uploads.');
        }
        if (file_put_contents($publicFull, $binary) === false) {
            throw new RuntimeException('Could not publish attachment for sending. Check permissions on public/uploads.');
        }

        Database::execute(
            'INSERT INTO mail_attachments (message_id, filename, content_type, size_bytes, storage_path, resend_attachment_id)
             VALUES (:mid, :fn, :ct, :sz, :sp, :rid)',
            [
                'mid' => $messageId,
                'fn' => $filename,
                'ct' => $contentType,
                'sz' => strlen($binary),
                'sp' => $rel,
                'rid' => $resendAttachmentId,
            ]
        );

        $base = rtrim((string) Config::get('APP_URL', ''), '/');
        return [
            'id' => (int) Database::lastId(),
            'path' => $base . '/' . $publicRel,
            'filename' => $filename,
        ];
    }

    /**
     * Shrink large images before emailing (shared hosts choke on multi‑MB base64 payloads).
     */
    private function maybeCompressAttachmentBinary(string $filename, string $mime, string $binary): array
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $isImage = str_starts_with($mime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
        if (!$isImage || strlen($binary) < 700000 || !extension_loaded('gd')) {
            return [$filename, $mime, $binary];
        }

        $tmp = tempnam(sys_get_temp_dir(), 'mailimg');
        if ($tmp === false) {
            return [$filename, $mime, $binary];
        }
        file_put_contents($tmp, $binary);
        $src = $this->gdLoad($tmp);
        @unlink($tmp);
        if ($src === null) {
            return [$filename, $mime, $binary];
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $maxEdge = 1600;
        $scale = min(1.0, $maxEdge / max($w, $h, 1));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);

        ob_start();
        imagejpeg($dst, null, 82);
        imagedestroy($dst);
        $out = (string) ob_get_clean();
        if ($out === '' || strlen($out) >= strlen($binary)) {
            return [$filename, $mime, $binary];
        }

        $newName = preg_replace('/\.[^.]+$/', '', $filename) ?: $filename;
        return [$newName . '.jpg', 'image/jpeg', $out];
    }

    /**
     * @param array<string, mixed> $files
     * @return list<array{filename:string,path:string}>
     */
    private function storeUploadedAttachments(int $messageId, array $files, string $kind): array
    {
        $payload = [];
        if (!isset($files['name']) || !is_array($files['name'])) {
            return $payload;
        }
        $n = count($files['name']);
        for ($i = 0; $i < $n; $i++) {
            if ((int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $tmp = (string) ($files['tmp_name'][$i] ?? '');
            $filename = (string) $files['name'][$i];
            if ($tmp === '' || !is_readable($tmp)) {
                throw new RuntimeException('Could not read uploaded file "' . $filename . '". Try attaching it again.');
            }
            $bin = file_get_contents($tmp);
            if ($bin === false || $bin === '') {
                throw new RuntimeException('Could not read uploaded file "' . $filename . '".');
            }
            $mime = (string) ($files['type'][$i] ?? 'application/octet-stream');
            [$filename, $mime, $bin] = $this->maybeCompressAttachmentBinary($filename, $mime, $bin);
            $stored = $this->storeAttachmentFile($messageId, $filename, $mime, $bin, $kind);
            $payload[] = [
                'filename' => $filename,
                'path' => $stored['path'],
            ];
        }
        return $payload;
    }

    /** @return list<string> */
    private function parseAddressList(string $raw): array
    {
        $parts = preg_split('/[,;]+/', $raw) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '') {
                continue;
            }
            if (preg_match('/<([^>]+)>/', $p, $m)) {
                $p = $m[1];
            }
            $p = strtolower(trim($p));
            if (filter_var($p, FILTER_VALIDATE_EMAIL)) {
                $out[] = $p;
            }
        }
        return array_values(array_unique($out));
    }

    private function parseScheduleLocal(string $raw): DateTimeImmutable
    {
        $tz = new DateTimeZone('Africa/Lagos');
        $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $raw, $tz)
            ?: DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $raw, $tz);
        if (!$dt) {
            throw new RuntimeException('Invalid schedule time.');
        }
        $now = new DateTimeImmutable('now', $tz);
        if ($dt < $now->modify('+1 minute')) {
            throw new RuntimeException('Schedule at least 1 minute ahead.');
        }
        if ($dt > $now->modify('+90 days')) {
            throw new RuntimeException('Schedule within 90 days.');
        }
        return $dt;
    }

    private function prefixSubject(string $subject, string $prefix): string
    {
        $subject = trim($subject);
        if (stripos($subject, $prefix) === 0) {
            return $subject;
        }
        return $prefix . ' ' . $subject;
    }

    /** @param array<string, mixed> $src */
    private function quoteBlock(array $src): string
    {
        $when = $this->formatListDate((string) ($src['received_at'] ?? $src['sent_at'] ?? $src['created_at'] ?? ''));
        $sender = $this->displaySender($src);
        $email = (string) $src['from_email'];
        $body = MailHtml::sanitize((string) ($src['body_html'] ?? nl2br(htmlspecialchars((string) ($src['body_text'] ?? ''), ENT_QUOTES))));
        return '<div class="lb-mail-quote">On ' . htmlspecialchars($when, ENT_QUOTES)
            . ', ' . htmlspecialchars($sender, ENT_QUOTES)
            . ' &lt;' . htmlspecialchars($email, ENT_QUOTES) . '&gt; wrote:'
            . '<blockquote style="margin:0 0 0 8px;padding-left:12px;border-left:2px solid #e1e5ee">'
            . $body . '</blockquote></div>';
    }

    /** @return \GdImage|null */
    private function gdLoad(string $path): mixed
    {
        $info = @getimagesize($path);
        if ($info === false) {
            return null;
        }
        return match ($info[2] ?? 0) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : null,
            default => null,
        };
    }
}
