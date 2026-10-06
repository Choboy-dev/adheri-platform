<?php

declare(strict_types=1);

namespace Adheri\Mail;

final class MailCsrf
{
    private const KEY = '_mail_csrf';

    public static function token(): string
    {
        $existing = MailSession::get(self::KEY);
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }
        $token = bin2hex(random_bytes(32));
        MailSession::set(self::KEY, $token);
        return $token;
    }

    public static function field(): string
    {
        $t = htmlspecialchars(self::token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<input type="hidden" name="_csrf" value="' . $t . '">';
    }

    public static function validate(?string $token): bool
    {
        $expected = MailSession::get(self::KEY);
        return is_string($expected)
            && is_string($token)
            && $token !== ''
            && hash_equals($expected, $token);
    }

    public static function requireValid(): void
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!self::validate(is_string($token) ? $token : null)) {
            http_response_code(419);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Invalid CSRF token.';
            exit;
        }
    }
}
