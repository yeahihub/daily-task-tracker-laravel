<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PasswordResetControllerTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function guest_can_view_forgot_password_form(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertViewIs('auth.forgot-password');
    }

    #[Test]
    public function authenticated_user_cannot_view_forgot_password_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('password.request'));

        $response->assertRedirect(route('dashboard'));
    }

    #[Test]
    public function password_reset_link_is_sent_for_existing_user(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post(route('password.email'), ['email' => $user->email]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    #[Test]
    public function password_reset_request_returns_status_for_nonexistent_email(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), ['email' => 'nobody@example.com']);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public static function invalidPasswordResetEmailDataProvider(): array
    {
        return [
            'missing email'        => [
                ['email' => ''],
                'email',
            ],
            'invalid email format' => [
                ['email' => 'not-an-email'],
                'email',
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidPasswordResetEmailDataProvider')]
    public function password_reset_email_fails_with_invalid_data(array $data, string $expectedErrorField): void
    {
        $response = $this->post(route('password.email'), $data);

        $response->assertInvalid($expectedErrorField);
    }

    #[Test]
    public function guest_can_view_password_reset_form(): void
    {
        $response = $this->get(route('password.reset', ['token' => 'test-token', 'email' => 'test@example.com']));

        $response->assertOk();
        $response->assertViewIs('auth.reset-password');
        $response->assertViewHas('token', 'test-token');
    }

    #[Test]
    public function user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create();

        $token       = Password::createToken($user);
        $newPassword = Str::password();

        $response = $this->post(
            route('password.store'),
            [
                'token'                 => $token,
                'email'                 => $user->email,
                'password'              => $newPassword,
                'password_confirmation' => $newPassword,
            ]
        );

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
    }

    #[Test]
    public function password_reset_fails_with_invalid_token(): void
    {
        $user        = User::factory()->create();
        $newPassword = Str::password();

        $response = $this->post(
            route('password.store'),
            [
                'token'                 => 'invalid-token',
                'email'                 => $user->email,
                'password'              => $newPassword,
                'password_confirmation' => $newPassword,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
    }

    public static function invalidResetPasswordDataProvider(): array
    {
        return [
            'missing token'                  => [
                [
                    'token'                 => '',
                    'email'                 => 'user@example.com',
                    'password'              => 'Password1!',
                    'password_confirmation' => 'Password1!',
                ],
                'token',
            ],
            'missing email'                  => [
                [
                    'token'                 => 'some-token',
                    'email'                 => '',
                    'password'              => 'Password1!',
                    'password_confirmation' => 'Password1!',
                ],
                'email',
            ],
            'invalid email'                  => [
                [
                    'token'                 => 'some-token',
                    'email'                 => 'not-valid',
                    'password'              => 'Password1!',
                    'password_confirmation' => 'Password1!',
                ],
                'email',
            ],
            'missing password'               => [
                [
                    'token'                 => 'some-token',
                    'email'                 => 'user@example.com',
                    'password'              => '',
                    'password_confirmation' => '',
                ],
                'password',
            ],
            'password confirmation mismatch' => [
                [
                    'token'                 => 'some-token',
                    'email'                 => 'user@example.com',
                    'password'              => 'Password1!',
                    'password_confirmation' => 'Different1!',
                ],
                'password',
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidResetPasswordDataProvider')]
    public function password_reset_fails_with_invalid_data(array $data, string $expectedErrorField): void
    {
        $response = $this->post(route('password.store'), $data);

        $response->assertInvalid($expectedErrorField);
    }
}
