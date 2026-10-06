<?php

declare(strict_types=1);

namespace Adheri\Http\Controllers\Admin;

use Adheri\Http\Controller;
use Adheri\Http\Request;
use Adheri\Http\Response;
use Adheri\Modules\Auth\PasswordRecoveryService;
use Adheri\Security\Auth;
use Adheri\Support\Flash;
use Adheri\Support\Url;
use Adheri\View;

final class PasswordRecoveryController extends Controller
{
    public function __construct(
        private readonly PasswordRecoveryService $recovery = new PasswordRecoveryService(),
    ) {
    }

    public function forgotForm(Request $request): never
    {
        if (Auth::check()) {
            Response::redirect(Url::to('admin'));
        }
        View::make('admin/password/forgot', [
            'title' => 'Forgot password',
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
            'email' => (string) (Flash::get('recovery_email') ?? ''),
        ], 'layouts/admin-guest');
    }

    public function forgotSubmit(Request $request): never
    {
        $this->requireCsrf($request);
        $email = $request->string('email');
        $channel = $request->string('channel');
        Flash::set('recovery_email', $email);

        $result = $this->recovery->start(PasswordRecoveryService::REALM_ADMIN, $email, $channel);
        if (!$result['ok']) {
            Flash::error((string) ($result['error'] ?? 'Could not start password reset.'));
            Response::redirect(Url::to('admin/forgot-password'));
        }

        $_SESSION['recovery_challenge_id'] = (string) $result['challenge_id'];
        $_SESSION['recovery_delivery_masked'] = (string) ($result['delivery_email_masked'] ?? '');
        Flash::success((string) ($result['message'] ?? 'Code sent.'));
        Response::redirect(Url::to('admin/verify-otp'));
    }

    public function verifyForm(Request $request): never
    {
        if (Auth::check()) {
            Response::redirect(Url::to('admin'));
        }
        $challengeId = (string) ($_SESSION['recovery_challenge_id'] ?? '');
        if ($challengeId === '') {
            Flash::error('Start password recovery first.');
            Response::redirect(Url::to('admin/forgot-password'));
        }

        View::make('admin/password/verify', [
            'title' => 'Enter verification code',
            'error' => Flash::get('error'),
            'success' => Flash::get('success'),
            'deliveryMasked' => (string) ($_SESSION['recovery_delivery_masked'] ?? ''),
        ], 'layouts/admin-guest');
    }

    public function verifySubmit(Request $request): never
    {
        $this->requireCsrf($request);
        $challengeId = (string) ($_SESSION['recovery_challenge_id'] ?? '');
        $otp = $request->string('otp');
        $result = $this->recovery->verifyOtp($challengeId, $otp);
        if (!$result['ok']) {
            Flash::error((string) ($result['error'] ?? 'Invalid code.'));
            Response::redirect(Url::to('admin/verify-otp'));
        }
        $_SESSION['recovery_reset_token'] = (string) $result['reset_token'];
        unset($_SESSION['recovery_challenge_id']);
        Response::redirect(Url::to('admin/reset-password'));
    }

    public function resetForm(Request $request): never
    {
        if (Auth::check()) {
            Response::redirect(Url::to('admin'));
        }
        $token = (string) ($_SESSION['recovery_reset_token'] ?? '');
        if ($token === '') {
            Flash::error('Verify your code before setting a new password.');
            Response::redirect(Url::to('admin/forgot-password'));
        }
        View::make('admin/password/reset', [
            'title' => 'Set new password',
            'error' => Flash::get('error'),
        ], 'layouts/admin-guest');
    }

    public function resetSubmit(Request $request): never
    {
        $this->requireCsrf($request);
        $token = (string) ($_SESSION['recovery_reset_token'] ?? '');
        $result = $this->recovery->resetPassword(
            $token,
            $request->string('password'),
            $request->string('password_confirm')
        );
        if (!$result['ok']) {
            Flash::error((string) ($result['error'] ?? 'Could not update password.'));
            Response::redirect(Url::to('admin/reset-password'));
        }
        unset($_SESSION['recovery_reset_token'], $_SESSION['recovery_delivery_masked'], $_SESSION['recovery_challenge_id']);
        Flash::success('Password updated. Sign in with your new password.');
        Response::redirect(Url::to('admin/login'));
    }
}
