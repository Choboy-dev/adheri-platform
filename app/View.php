<?php

declare(strict_types=1);

namespace Adheri;

use RuntimeException;

final class View
{
    private static string $path = '';

    public static function boot(string $viewsPath): void
    {
        self::$path = rtrim($viewsPath, '/');
    }

    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = []): string
    {
        $file = self::$path . '/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View [{$view}] not found.");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    /** @param array<string, mixed> $data */
    public static function make(string $view, array $data = [], ?string $layout = null): never
    {
        $content = self::render($view, $data);
        if ($layout !== null) {
            $content = self::render($layout, array_merge($data, ['content' => $content]));
        }
        \Adheri\Http\Response::html($content);
    }
}
