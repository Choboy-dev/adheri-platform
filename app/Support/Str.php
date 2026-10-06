<?php

declare(strict_types=1);

namespace Adheri\Support;

final class Str
{
    public static function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-') ?: 'item';
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function excerpt(string $value, int $limit = 160): string
    {
        $plain = trim(strip_tags($value));
        if (mb_strlen($plain) <= $limit) {
            return $plain;
        }
        return rtrim(mb_substr($plain, 0, $limit - 1)) . '…';
    }
}
