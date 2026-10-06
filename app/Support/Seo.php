<?php

declare(strict_types=1);

namespace Adheri\Support;

/**
 * Open Graph / Twitter / canonical meta for public pages.
 * Featured image when available; otherwise ADHERI logo.
 */
final class Seo
{
    public const LOGO_PATH = 'img/ADHERI-new-logo-1.png';

    /**
     * @param array<string, string|null> $settings
     * @param array{
     *   description?: string|null,
     *   image?: string|null,
     *   url?: string|null,
     *   type?: string|null,
     *   title?: string|null
     * } $overrides
     * @return array{
     *   title: string,
     *   description: string,
     *   image: string,
     *   url: string,
     *   type: string,
     *   site_name: string,
     *   locale: string
     * }
     */
    public static function meta(array $settings, string $pageTitle, array $overrides = []): array
    {
        $org = (string) ($settings['org_short_name'] ?? $settings['org_name'] ?? 'ADHERI');
        $siteName = (string) ($settings['org_name'] ?? $org);

        $title = trim((string) ($overrides['title'] ?? $pageTitle));
        if ($title === '') {
            $title = (string) ($settings['seo_default_title'] ?? $siteName);
        }

        $description = trim((string) ($overrides['description'] ?? ''));
        if ($description === '') {
            $description = trim((string) ($settings['seo_default_description'] ?? ''));
        }
        if ($description === '') {
            $description = $siteName . ' — Advocates for Health and Rights of Women and Youths Initiative.';
        }
        $description = self::plain($description, 200);

        $image = self::absoluteImage($overrides['image'] ?? null);
        if ($image === '') {
            $image = Url::asset(self::LOGO_PATH);
        }

        $url = trim((string) ($overrides['url'] ?? ''));
        if ($url === '') {
            $url = self::currentUrl();
        } elseif (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = Url::to($url);
        }

        $type = (string) ($overrides['type'] ?? 'website');
        if (!in_array($type, ['website', 'article'], true)) {
            $type = 'website';
        }

        return [
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'url' => $url,
            'type' => $type,
            'site_name' => $siteName,
            'locale' => 'en_NG',
        ];
    }

    public static function absoluteImage(?string $path): string
    {
        if ($path === null) {
            return '';
        }
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return Url::media($path);
    }

    public static function currentUrl(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName !== '/' && str_starts_with($path, $scriptName)) {
            $path = substr($path, strlen($scriptName)) ?: '/';
        }

        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        if ($path === '/') {
            return Url::base() . '/';
        }

        return Url::to(ltrim($path, '/'));
    }

    public static function plain(string $text, int $max = 200): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        return rtrim(mb_substr($text, 0, $max - 1)) . '…';
    }
}
