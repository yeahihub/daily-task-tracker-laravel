<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Routing\Redirector;
use Illuminate\Contracts\View\View;
use App\Actions\Profile\DeleteProfile;
use App\Actions\Profile\UpdateProfile;
use Illuminate\Contracts\View\Factory;
use App\Actions\Profile\UpdatePassword;
use App\Http\Requests\DeleteProfileRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;

readonly class ProfileController
{
    public function __construct(private Factory $view, private Redirector $redirector, private AuthManager $auth)
    {
    }

    public function edit(Request $request): View
    {
        $user = $request->user();

        return $this->view->make(
            'profile.edit',
            [
                'name'               => $user->name,
                'email'              => $user->email,
                'needsToVerifyEmail' => $user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail(),
            ]
        );
    }

    public function update(ProfileUpdateRequest $request, UpdateProfile $updateProfile)
    {
        $updateProfile->execute($request->user(), $request->validated());

        return $this->redirector->route('profile.edit')->with('status', 'profile-updated');
    }

    public function updatePassword(UpdatePasswordRequest $request, UpdatePassword $updatePassword)
    {
        $updatePassword->execute($request->user(), $request->validated('password'));

        return $this->redirector->back()->with('status', 'password-updated');
    }

    public function destroy(DeleteProfileRequest $request, DeleteProfile $deleteProfile)
    {
        $user = $request->user();

        $this->auth->logout();

        $deleteProfile->execute($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->redirector->to('/');
    }
}
