<?php

declare(strict_types=1);

namespace Adheri\Http\Controllers\Admin;

use Adheri\Config;
use Adheri\Database;
use Adheri\Http\Controller;
use Adheri\Http\Request;
use Adheri\Http\Response;
use Adheri\Security\Auth;
use Adheri\Support\Flash;
use Adheri\Support\Url;
use Adheri\View;

final class AuthController extends Controller
{
    public function loginForm(Request $request): never
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user !== null && Auth::needsAccountSetup($user)) {
                Response::redirect(Url::to('admin/account-setup'));
            }
            Response::redirect(Url::to('admin'));
        }
        View::make('admin/login', [
            'title' => 'Admin login',
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
            'email' => (string) (Flash::get('login_email') ?? $this->defaultAdminEmail()),
        ], 'layouts/admin-guest');
    }

    public function login(Request $request): never
    {
        $this->requireCsrf($request);
        $email = $request->string('email');
        $ok = Auth::attempt($email, $request->string('password'));
        if (!$ok) {
            Flash::error('Invalid email or password.');
            Flash::set('login_email', $email);
            Response::redirect(Url::to('admin/login'));
        }
        $user = Auth::user();
        if ($user !== null && Auth::needsAccountSetup($user)) {
            Response::redirect(Url::to('admin/account-setup'));
        }
        Response::redirect(Url::to('admin'));
    }

    public function logout(Request $request): never
    {
        $this->requireCsrf($request);
        Auth::logout();
        Response::redirect(Url::to('admin/login'));
    }

    private function defaultAdminEmail(): string
    {
        $configured = trim((string) Config::get('ADMIN_EMAIL', ''));
        if ($configured !== '') {
            return $configured;
        }

        try {
            $row = Database::fetch(
                'SELECT email FROM admin_users WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
            );
            if ($row && !empty($row['email'])) {
                return (string) $row['email'];
            }
        } catch (\Throwable) {
            // fall through
        }

        return (string) Config::get('MAIL_FROM', 'admin@advocatesforhealthandrights.org');
    }
}
