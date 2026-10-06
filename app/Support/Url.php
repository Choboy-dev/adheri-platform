<?php

declare(strict_types=1);

namespace Adheri\Support;

use Adheri\Config;

final class Url
{
    public static function base(): string
    {
        $configured = rtrim((string) Config::get('APP_URL', ''), '/');
        if ($configured !== '') {
            return $configured;
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        return rtrim($scheme . '://' . $host . $script, '/');
    }

    public static function to(string $path = '/'): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return self::base() . '/' . ltrim($path, '/');
    }

    public static function asset(string $path): string
    {
        return self::to('assets/' . ltrim($path, '/'));
    }

    /**
     * Resolve a stored media/photo path for the public site.
     * Seeded assets live under assets/; uploads live under public/uploads.
     */
    public static function media(string $path): string
    {
        $path = ltrim($path, '/');
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, 'uploads/') || str_starts_with($path, 'assets/')) {
            return self::to($path);
        }
        return self::asset($path);
    }
}
