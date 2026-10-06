<?php

declare(strict_types=1);

namespace Adheri\Http;

final class Response
{
    public static function html(string $content, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $content;
        exit;
    }

    public static function redirect(string $url, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    /** @param array<string, mixed> $data */
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_THROW_ON_ERROR);
        exit;
    }

    public static function notFound(string $message = 'Page not found'): never
    {
        self::html($message, 404);
    }
}
