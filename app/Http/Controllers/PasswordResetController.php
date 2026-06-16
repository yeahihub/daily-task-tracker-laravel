<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use SensitiveParameter;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Illuminate\Routing\Redirector;
use App\Actions\Auth\ResetPassword;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Password;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Contracts\Routing\UrlGenerator;
use App\Http\Requests\SendPasswordResetEmailRequest;
use Illuminate\Auth\Passwords\PasswordBrokerManager;

readonly class PasswordResetController
{
    public function __construct(
        private Factory $view,
        private Redirector $redirector,
        private UrlGenerator $url,
        private LoggerInterface $logger,
    ) {
    }

    public function showPasswordResetRequestForm(): View
    {
        return $this->view->make('auth.forgot-password');
    }

    public function sendPasswordResetEmail(
        SendPasswordResetEmailRequest $request,
        PasswordBrokerManager $passwordBrokerManager
    ): RedirectResponse {
        $email = $request->string('email');

        $passwordBrokerManager->sendResetLink(['email' => $email]);

        return $this->redirector->back()
            ->with('status', 'If an account with this email exists, we will send a password reset link.');
    }

    public function showPasswordResetForm(#[SensitiveParameter] string $token, Request $request): View
    {
        return $this->view->make(
            'auth.reset-password',
            [
                'token' => $token,
                'email' => $request->string('email'),
            ]
        );
    }

    public function resetPassword(
        ResetPasswordRequest $request,
        PasswordBrokerManager $passwordBrokerManager,
        ResetPassword $resetPassword
    ): RedirectResponse {
        $requestData = $request->validated();

        $status = $passwordBrokerManager->reset(
            $requestData,
            fn(User $user, #[SensitiveParameter] string $newPassword) => $resetPassword->execute(
                $user,
                $newPassword
            )
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->redirector->to($this->url->route('login'))
                ->with('status', __($status));
        }

        $this->logger->debug(
            'Password reset failed',
            [
                'status' => $status,
                'email'  => $requestData['email'] ?? null,
            ]
        );

        return $this->redirector->back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Failed to reset your password.']);
    }
}
