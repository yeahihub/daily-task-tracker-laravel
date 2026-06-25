<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Events\Dispatcher;

readonly class RegisterUser
{
    public function __construct(private Hasher $hasher, private Dispatcher $dispatcher)
    {
        //
    }

    public function execute(array $data): User
    {
        $user = User::create(
            [
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => $this->hasher->make($data['password']),
            ]
        );

        $this->dispatcher->dispatch(new Registered($user));

        return $user;
    }
}
