<?php

declare(strict_types=1);

namespace Adheri\Security;

final class Csrf
{
    private const KEY = '_csrf_token';

    public static function boot(): void
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
    }

    public static function token(): string
    {
        self::boot();
        return (string) $_SESSION[self::KEY];
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_token" value="' . $token . '">';
    }

    public static function validate(?string $token): bool
    {
        return is_string($token)
            && isset($_SESSION[self::KEY])
            && hash_equals((string) $_SESSION[self::KEY], $token);
    }
}
