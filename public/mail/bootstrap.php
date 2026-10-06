<?php

declare(strict_types=1);

/**
 * Metro Mail bootstrap — independent of site Auth / Router.
 */

$mailRoot = dirname(__DIR__, 2);

spl_autoload_register(static function (string $class) use ($mailRoot): void {
    $prefix = 'Adheri\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $path = $mailRoot . '/app/' . $relative . '.php';
    if (is_file($path)) {
        require $path;
    }
});

use Adheri\Config;
use Adheri\Database;
use Adheri\Mail\MailCsrf;
use Adheri\Mail\MailService;
use Adheri\Mail\MailSession;

Config::load($mailRoot);
Database::boot();
MailSession::start();

$mail = new MailService();
$mail->ensureStorage();

function mail_h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function mail_url(string $path = ''): string
{
    global $mail;
    return $mail->url($path);
}

function mail_path(string $suffix = ''): string
{
    global $mail;
    $base = $mail->path();
    $suffix = ltrim($suffix, '/');
    return $suffix === '' ? $base : $base . '/' . $suffix;
}

function mail_asset(string $path): string
{
    $base = rtrim((string) Config::get('APP_URL', ''), '/');
    return $base . '/assets/' . ltrim($path, '/');
}

function csrf_field(): string
{
    return MailCsrf::field();
}

function require_csrf(): void
{
    MailCsrf::requireValid();
}

function mail_send_security_headers(): void
{
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; script-src 'self' 'unsafe-inline'; connect-src 'self'; frame-src 'self'; base-uri 'self'; form-action 'self'");
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('X-Content-Type-Options: nosniff');
}

/** @return array<string, string> */
function mail_icon_paths(): array
{
    return [
        'inbox' => 'M4 6h16v12H4z M4 6l8 6 8-6',
        'star' => 'M12 3l2.5 6.5L21 10l-5 4.2L17.5 21 12 17.5 6.5 21 8 14.2 3 10l6.5-.5z',
        'sent' => 'M4 12l16-8-6 16-2-6-8-2z',
        'drafts' => 'M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z M14 3v6h6',
        'scheduled' => 'M12 7v5l3 2 M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z',
        'archive' => 'M3 7h18l-2 13H5L3 7z M3 7l2-3h14l2 3',
        'spam' => 'M12 3l9 16H3L12 3z M12 10v4 M12 17h.01',
        'trash' => 'M4 7h16 M9 7V5h6v2 M6 7l1 14h10l1-14',
        'settings' => 'M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z M4 12h2 M18 12h2 M12 4v2 M12 18v2',
        'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M22 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75',
        'compose' => 'M12 5v14 M5 12h14',
        'menu' => 'M4 7h16 M4 12h16 M4 17h16',
        'search' => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16z M21 21l-4.3-4.3',
        'bell' => 'M6 9a6 6 0 1 1 12 0c0 7 3 7 3 7H3s3 0 3-7 M10 19a2 2 0 0 0 4 0',
        'bell-off' => 'M6 9a6 6 0 0 1 10.5-3.5 M18 12c0 4 3 6 3 6H8 M10 19a2 2 0 0 0 4 0 M3 3l18 18',
        'logout' => 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4 M16 17l5-5-5-5 M21 12H9',
        'back' => 'M15 18l-6-6 6-6',
    ];
}

function mail_icon(string $name, string $class = 'mail-icon'): string
{
    $paths = mail_icon_paths();
    $d = $paths[$name] ?? 'M4 4h16v16H4z';
    $parts = preg_split('/\s+M/', 'M' . ltrim($d, 'M')) ?: [];
    $svgPaths = '';
    foreach ($parts as $i => $part) {
        $part = ($i === 0 ? $part : 'M' . $part);
        if (trim($part) === '' || $part === 'M') {
            continue;
        }
        // Multi-path icons stored space-separated path commands — use single path for simplicity
    }
    return '<svg class="' . mail_h($class) . '" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . mail_h($d) . '"/></svg>';
}

/**
 * @param array<string, mixed> $user
 * @param array<string, int> $counts
 * @return list<array{key:string,label:string,href:string,badge?:int,icon:string}>
 */
function mail_nav_items(array $user, array $counts): array
{
    $items = [
        ['key' => 'inbox', 'label' => 'Inbox', 'href' => mail_path() . '?folder=inbox', 'badge' => $counts['inbox_unread'] ?? 0, 'icon' => 'inbox'],
        ['key' => 'starred', 'label' => 'Starred', 'href' => mail_path() . '?folder=starred', 'badge' => $counts['starred'] ?? 0, 'icon' => 'star'],
        ['key' => 'sent', 'label' => 'Sent', 'href' => mail_path() . '?folder=sent', 'icon' => 'sent'],
        ['key' => 'drafts', 'label' => 'Drafts', 'href' => mail_path() . '?folder=drafts', 'badge' => $counts['drafts'] ?? 0, 'icon' => 'drafts'],
        ['key' => 'scheduled', 'label' => 'Scheduled', 'href' => mail_path() . '?folder=scheduled', 'badge' => $counts['scheduled'] ?? 0, 'icon' => 'scheduled'],
        ['key' => 'archive', 'label' => 'Archive', 'href' => mail_path() . '?folder=archive', 'icon' => 'archive'],
        ['key' => 'spam', 'label' => 'Spam', 'href' => mail_path() . '?folder=spam', 'icon' => 'spam'],
        ['key' => 'trash', 'label' => 'Trash', 'href' => mail_path() . '?folder=trash', 'icon' => 'trash'],
    ];
    return $items;
}
