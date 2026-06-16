<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use App\Actions\Auth\RegisterUser;
use Illuminate\Routing\Redirector;
use App\Http\Requests\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\RegisterRequest;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Routing\UrlGenerator;

readonly class AuthController
{
    public function __construct(
        private AuthManager $auth,
        private Redirector $redirector,
        private UrlGenerator $url,
        private Factory $view
    ) {
    }

    public function showLoginForm(): View
    {
        return $this->view->make('auth.login');
    }

    public function showRegistrationForm(): View
    {
        return $this->view->make('auth.register');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if ($this->auth->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();

            return $this->redirector->intended($this->url->route('dashboard', absolute: false));
        }

        return $this->redirector->back()
            ->withErrors(['email' => 'These credentials do not match our records.'])
            ->withInput($request->except('password'));
    }

    public function register(RegisterRequest $request, RegisterUser $registerUser): RedirectResponse
    {
        $user = $registerUser->execute($request->validated());

        $this->auth->login($user);

        return $this->redirector->intended($this->url->route('dashboard', absolute: false));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->auth->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->redirector->to('/');
    }
}
