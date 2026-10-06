<?php

declare(strict_types=1);

namespace Adheri\Http;

use Adheri\Security\Auth;
use Adheri\Security\Csrf;
use Adheri\Support\Flash;
use Adheri\View;

abstract class Controller
{
    /** @param array<string, mixed> $data */
    protected function view(string $view, array $data = [], string $layout = 'layouts/public'): never
    {
        View::make($view, $data, $layout);
    }

    /** @param array<string, mixed> $data */
    protected function adminView(string $view, array $data = []): never
    {
        Auth::requireLogin();
        $data['authUser'] = Auth::user();
        View::make($view, $data, 'layouts/admin');
    }

    protected function requireCsrf(Request $request): void
    {
        if (!Csrf::validate($request->string('_token'))) {
            Flash::error('Invalid security token. Please try again.');
            Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }
}
