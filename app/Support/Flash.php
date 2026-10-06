<?php

declare(strict_types=1);

namespace Adheri\Support;

final class Flash
{
    public static function boot(): void
    {
        // no-op; session already started
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!isset($_SESSION['_flash'][$key])) {
            return $default;
        }
        $value = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function success(string $message): void
    {
        self::set('success', $message);
    }

    public static function error(string $message): void
    {
        self::set('error', $message);
    }

    /** @param array<string, string> $errors */
    public static function errors(array $errors): void
    {
        self::set('errors', $errors);
    }

    /** @return array<string, string> */
    public static function pullErrors(): array
    {
        $errors = self::get('errors', []);
        return is_array($errors) ? $errors : [];
    }
}
