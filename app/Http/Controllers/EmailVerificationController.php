<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

readonly class EmailVerificationController
{
    public function __construct(
        private Factory $view,
        private Redirector $redirector,
        private UrlGenerator $url,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->redirector->intended($this->url->route('dashboard'));
        }

        return $this->view->make('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return $this->redirector->intended($this->url->route('dashboard'));
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->redirector->intended($this->url->route('dashboard'));
        }

        $request->user()->sendEmailVerificationNotification();

        return $this->redirector->back()->with('status', 'verification-link-sent');
    }
}
