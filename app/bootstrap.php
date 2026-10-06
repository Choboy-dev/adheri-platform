<?php

declare(strict_types=1);

use Adheri\Config;
use Adheri\Database;
use Adheri\Http\Request;
use Adheri\Router;
use Adheri\Security\Auth;
use Adheri\Security\Csrf;
use Adheri\Support\Flash;
use Adheri\View;

$root = dirname(__DIR__);

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'Adheri\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $path = $root . '/app/' . $relative . '.php';
    if (is_file($path)) {
        require $path;
    }
});

Config::load($root);
date_default_timezone_set('Africa/Lagos');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(Config::get('SESSION_NAME', 'adheri_session'));
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

Database::boot();
Flash::boot();
Csrf::boot();
Auth::boot();
View::boot($root . '/resources/views');

$request = Request::fromGlobals();
$router = new Router();

require $root . '/routes/web.php';
require $root . '/routes/admin.php';

$router->dispatch($request);
