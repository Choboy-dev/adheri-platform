<?php

declare(strict_types=1);

namespace Adheri;

final class Config
{
    /** @var array<string, string> */
    private static array $values = [];

    public static function load(string $root): void
    {
        $envFile = $root . '/.env';
        if (!is_file($envFile)) {
            $envFile = $root . '/.env.example';
        }

        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }
            self::$values[$key] = $value;
            $_ENV[$key] = $value;
        }

        self::$values['ROOT'] = $root;

        $configuredPublic = self::$values['PUBLIC_PATH'] ?? '';
        if ($configuredPublic !== '' && is_dir($configuredPublic)) {
            $publicPath = rtrim($configuredPublic, '/');
        } elseif (is_dir($root . '/public')) {
            $publicPath = $root . '/public';
        } elseif (is_dir($root . '/public_html')) {
            $publicPath = $root . '/public_html';
        } else {
            $publicPath = $root . '/public';
        }

        self::$values['PUBLIC_PATH'] = $publicPath;
        self::$values['UPLOAD_PATH'] = $publicPath . '/uploads';
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::$values[$key] ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = strtolower((string) self::get($key, $default ? 'true' : 'false'));
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        return (int) (self::get($key, (string) $default) ?? $default);
    }

    public static function root(): string
    {
        return self::$values['ROOT'] ?? dirname(__DIR__);
    }
}
