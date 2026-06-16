<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use SensitiveParameter;
use Illuminate\Support\Str;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Events\Dispatcher;

readonly class ResetPassword
{
    public function __construct(
        private Hasher $hasher,
        private Dispatcher $events,
    ) {
    }

    public function execute(User $user, #[SensitiveParameter] string $newPassword): void
    {
        $user->password       = $this->hasher->make($newPassword);
        $user->remember_token = Str::random(60);

        $user->save();

        $this->events->dispatch(new PasswordReset($user));
    }
}
