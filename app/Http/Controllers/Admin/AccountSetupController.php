<?php

declare(strict_types=1);

namespace Adheri\Http\Controllers\Admin;

use Adheri\Http\Controller;
use Adheri\Http\Request;
use Adheri\Http\Response;
use Adheri\Security\Auth;
use Adheri\Support\Flash;
use Adheri\Support\Url;
use Adheri\View;

final class AccountSetupController extends Controller
{
    public function form(Request $request): never
    {
        if (!Auth::check()) {
            Response::redirect(Url::to('admin/login'));
        }
        $user = Auth::user();
        if ($user === null) {
            Response::redirect(Url::to('admin/login'));
        }
        if (!Auth::needsAccountSetup($user)) {
            Response::redirect(Url::to('admin'));
        }

        View::make('admin/account-setup', [
            'title' => 'Finish account setup',
            'error' => Flash::get('error'),
            'email' => (string) ($user['email'] ?? ''),
            'recoveryEmail' => (string) (Flash::get('recovery_email_draft') ?? ''),
        ], 'layouts/admin-guest');
    }

    public function save(Request $request): never
    {
        $this->requireCsrf($request);
        if (!Auth::check()) {
            Response::redirect(Url::to('admin/login'));
        }

        $recovery = $request->string('recovery_email');
        Flash::set('recovery_email_draft', $recovery);
        $result = Auth::saveRecoveryEmail($recovery);
        if (!$result['ok']) {
            Flash::error((string) ($result['error'] ?? 'Could not save recovery email.'));
            Response::redirect(Url::to('admin/account-setup'));
        }

        Flash::success('Recovery email saved. You can reset your password with it anytime.');
        Response::redirect(Url::to('admin'));
    }
}
