<?php

declare(strict_types=1);

namespace Adheri;

use Adheri\Http\Request;
use Adheri\Http\Response;
use Closure;

final class Router
{
    /** @var list<array{methods: list<string>, pattern: string, action: callable|array{0: class-string, 1: string}}> */
    private array $routes = [];

    public function get(string $pattern, callable|array $action): void
    {
        $this->add(['GET'], $pattern, $action);
    }

    public function post(string $pattern, callable|array $action): void
    {
        $this->add(['POST'], $pattern, $action);
    }

    public function match(array $methods, string $pattern, callable|array $action): void
    {
        $this->add($methods, $pattern, $action);
    }

    /** @param list<string> $methods */
    private function add(array $methods, string $pattern, callable|array $action): void
    {
        $this->routes[] = [
            'methods' => array_map('strtoupper', $methods),
            'pattern' => $pattern,
            'action' => $action,
        ];
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if (!in_array($request->method, $route['methods'], true)) {
                continue;
            }

            $params = $this->matchPattern($route['pattern'], $request->path);
            if ($params === null) {
                continue;
            }

            $action = $route['action'];
            if (is_array($action)) {
                [$class, $method] = $action;
                $controller = new $class();
                $controller->{$method}($request, ...array_values($params));
                return;
            }

            $action($request, ...array_values($params));
            return;
        }

        Response::html(View::render('public/errors/404', [
            'title' => 'Page not found',
        ]), 404);
    }

    /** @return array<string, string>|null */
    private function matchPattern(string $pattern, string $path): ?array
    {
        $pattern = '/' . trim($pattern, '/');
        if ($pattern !== '/') {
            $pattern = rtrim($pattern, '/');
        }

        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }
        return $params;
    }
}
