<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AuthControllerTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    #[Test]
    public function guest_can_view_login_form(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertViewIs('auth.login');
    }

    #[Test]
    public function guest_can_view_registration_form(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertViewIs('auth.register');
    }

    #[Test]
    public function authenticated_user_cannot_view_login_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect(route('dashboard'));
    }

    #[Test]
    public function authenticated_user_cannot_view_registration_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('register'));

        $response->assertRedirect(route('dashboard'));
    }

    #[Test]
    public function user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->post(
            route('login.post'),
            [
                'email'    => $user->email,
                'password' => 'password',
            ]
        );

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->post(
            route('login.post'),
            [
                'email'    => $user->email,
                'password' => 'wrong-password',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function login_remembers_email_on_failure(): void
    {
        $user = User::factory()->create();

        $response = $this->from(route('login'))
            ->post(
                route('login.post'),
                [
                    'email'    => $user->email,
                    'password' => 'wrong-password',
                ]
            );

        $response->assertRedirect(route('login'));
        $response->assertSessionHasInput('email', $user->email);
    }

    public static function invalidLoginDataProvider(): array
    {
        return [
            'missing email'        => [
                ['email' => '', 'password' => 'password'],
                'email',
            ],
            'invalid email format' => [
                ['email' => 'not-an-email', 'password' => 'password'],
                'email',
            ],
            'missing password'     => [
                ['email' => 'user@example.com', 'password' => ''],
                'password',
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidLoginDataProvider')]
    public function login_fails_with_invalid_data(array $data, string $expectedErrorField): void
    {
        $response = $this->post(route('login.post'), $data);

        $response->assertInvalid($expectedErrorField);
        $this->assertGuest();
    }

    #[Test]
    public function user_can_register_with_valid_data(): void
    {
        $password = Str::password();

        $response = $this->post(
            route('register.post'),
            [
                'name'                  => 'John Doe',
                'email'                 => 'john@example.com',
                'password'              => $password,
                'password_confirmation' => $password,
            ]
        );

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'john@example.com', 'name' => 'John Doe']);
    }

    #[Test]
    public function registering_dispatches_registered_event(): void
    {
        Event::fake();

        $password = Str::password();

        $response = $this->post(
            route('register.post'),
            [
                'name'                  => 'John Doe',
                'email'                 => 'john@example.com',
                'password'              => $password,
                'password_confirmation' => $password,
            ]
        );

        $response->assertSessionDoesntHaveErrors();
        $response->assertStatus(302);
        Event::assertDispatched(Registered::class);
    }

    public static function invalidRegistrationDataProvider(): array
    {
        return [
            'missing name'                   => [
                [
                    'name'                  => '',
                    'email'                 => 'john@example.com',
                    'password'              => 'Password1!',
                    'password_confirmation' => 'Password1!',
                ],
                'name',
            ],
            'name too long'                  => [
                [
                    'name'                  => str_repeat('a', 256),
                    'email'                 => 'john@example.com',
                    'password'              => 'Password1!',
                    'password_confirmation' => 'Password1!',
                ],
                'name',
            ],
            'missing email'                  => [
                ['name' => 'John', 'email' => '', 'password' => 'Password1!', 'password_confirmation' => 'Password1!'],
                'email',
            ],
            'invalid email'                  => [
                [
                    'name'                  => 'John',
                    'email'                 => 'not-valid',
                    'password'              => 'Password1!',
                    'password_confirmation' => 'Password1!',
                ],
                'email',
            ],
            'missing password'               => [
                ['name' => 'John', 'email' => 'john@example.com', 'password' => '', 'password_confirmation' => ''],
                'password',
            ],
            'password confirmation mismatch' => [
                [
                    'name'                  => 'John',
                    'email'                 => 'john@example.com',
                    'password'              => 'Password1!',
                    'password_confirmation' => 'Different1!',
                ],
                'password',
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidRegistrationDataProvider')]
    public function registration_fails_with_invalid_data(array $data, string $expectedErrorField): void
    {
        $response = $this->post(route('register.post'), $data);

        $response->assertInvalid($expectedErrorField);
        $this->assertGuest();
    }

    #[Test]
    public function registration_fails_with_duplicate_email(): void
    {
        $existingUser = User::factory()->create();

        $password = Str::password();

        $response = $this->post(
            route('register.post'),
            [
                'name'                  => 'Another User',
                'email'                 => $existingUser->email,
                'password'              => $password,
                'password_confirmation' => $password,
            ]
        );

        $response->assertInvalid('email');
    }

    #[Test]
    public function authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    #[Test]
    public function guest_cannot_logout(): void
    {
        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function password_reset_notification_is_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post(
            route('password.email'),
            [
                'email' => $user->email,
            ]
        );

        $response->assertRedirectBackWithoutErrors();
        Notification::assertSentTo($user, ResetPassword::class);
    }
}
