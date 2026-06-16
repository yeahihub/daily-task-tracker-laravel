<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ProfileControllerTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    #[Test]
    public function authenticated_user_can_view_profile_edit_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertViewIs('profile.edit');
        $response->assertViewHas('name', $user->name);
        $response->assertViewHas('email', $user->email);
    }

    #[Test]
    public function profile_edit_shows_verification_status_for_unverified_user(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertViewHas('needsToVerifyEmail', true);
    }

    #[Test]
    public function profile_edit_shows_verified_status_for_verified_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertViewHas('needsToVerifyEmail', false);
    }

    #[Test]
    public function guest_cannot_view_profile_edit_page(): void
    {
        $response = $this->get(route('profile.edit'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_update_profile_information(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(
            route('profile.update'),
            [
                'name'  => 'Updated Name',
                'email' => 'updated@example.com',
            ]
        );

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'profile-updated');

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('updated@example.com', $user->email);
    }

    #[Test]
    public function updating_profile_with_same_email_does_not_reset_email_verified_at(): void
    {
        $user               = User::factory()->create();
        $originalVerifiedAt = $user->email_verified_at;

        $response = $this->actingAs($user)->patch(
            route('profile.update'),
            [
                'name'  => 'New Name',
                'email' => $user->email,
            ]
        );

        $response->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($originalVerifiedAt->equalTo($user->email_verified_at));
    }

    #[Test]
    public function updating_profile_with_new_email_resets_email_verified_at(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(
            route('profile.update'),
            [
                'name'  => $user->name,
                'email' => 'newemail@example.com',
            ]
        );

        $response->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertSame('newemail@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    #[Test]
    public function profile_update_fails_with_email_belonging_to_another_user(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($user)->patch(
            route('profile.update'),
            [
                'name'  => $user->name,
                'email' => $otherUser->email,
            ]
        );

        $response->assertInvalid('email');
    }

    #[Test]
    public function guest_cannot_update_profile(): void
    {
        $response = $this->patch(
            route('profile.update'),
            [
                'name'  => 'Test',
                'email' => 'test@example.com',
            ]
        );

        $response->assertRedirect(route('login'));
    }

    public static function invalidProfileDataProvider(): array
    {
        return [
            'missing name'         => [
                ['name' => '', 'email' => 'valid@example.com'],
                'name',
            ],
            'name too long'        => [
                ['name' => str_repeat('a', 256), 'email' => 'valid@example.com'],
                'name',
            ],
            'missing email'        => [
                ['name' => 'Valid Name', 'email' => ''],
                'email',
            ],
            'invalid email format' => [
                ['name' => 'Valid Name', 'email' => 'not-an-email'],
                'email',
            ],
            'email too long'       => [
                ['name' => 'Valid Name', 'email' => str_repeat('a', 247) . '@test.com'],
                'email',
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidProfileDataProvider')]
    public function profile_update_fails_with_invalid_data(array $data, string $expectedErrorField): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profile.update'), $data);

        $response->assertInvalid($expectedErrorField);
    }

    #[Test]
    public function authenticated_user_can_update_password(): void
    {
        $user        = User::factory()->create();
        $newPassword = Str::password();

        $response = $this->actingAs($user)->put(
            route('profile.password.update'),
            [
                'current_password'      => 'password',
                'password'              => $newPassword,
                'password_confirmation' => $newPassword,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('status', 'password-updated');
        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
    }

    #[Test]
    public function password_update_fails_with_wrong_current_password(): void
    {
        $user        = User::factory()->create();
        $newPassword = Str::password();

        $response = $this->actingAs($user)->put(
            route('profile.password.update'),
            [
                'current_password'      => 'wrong-password',
                'password'              => $newPassword,
                'password_confirmation' => $newPassword,
            ]
        );

        $response->assertInvalid('current_password');
    }

    #[Test]
    public function password_update_fails_with_mismatched_confirmation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(
            route('profile.password.update'),
            [
                'current_password'      => 'password',
                'password'              => Str::password(),
                'password_confirmation' => Str::password(),
            ]
        );

        $response->assertInvalid('password');
    }

    #[Test]
    public function guest_cannot_update_password(): void
    {
        $newPassword = Str::password();

        $response = $this->put(
            route('profile.password.update'),
            [
                'current_password'      => 'password',
                'password'              => $newPassword,
                'password_confirmation' => $newPassword,
            ]
        );

        $response->assertRedirect(route('login'));
    }

    public static function invalidPasswordDataProvider(): array
    {
        $validPassword = 'V@lid-P4ssw0rd-' . Str::random(8);

        return [
            'missing current password' => [
                [
                    'current_password'      => '',
                    'password'              => $validPassword,
                    'password_confirmation' => $validPassword,
                ],
                'current_password',
            ],
            'missing new password'     => [
                [
                    'current_password'      => 'password',
                    'password'              => '',
                    'password_confirmation' => '',
                ],
                'password',
            ],
            'password too short'       => [
                [
                    'current_password'      => 'password',
                    'password'              => 'short',
                    'password_confirmation' => 'short',
                ],
                'password',
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidPasswordDataProvider')]
    public function password_update_fails_with_invalid_data(array $data, string $expectedErrorField): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('profile.password.update'), $data);

        $response->assertInvalid($expectedErrorField);
    }

    #[Test]
    public function authenticated_user_can_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete(
            route('profile.destroy'),
            [
                'password' => 'password',
            ]
        );

        $response->assertRedirect('/');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    #[Test]
    public function account_deletion_removes_related_data(): void
    {
        $user = User::factory()->create();
        $user->tasks()->create(
            [
                'title'     => 'Test Task',
                'task_date' => now()->toDateString(),
            ]
        );
        $user->categories()->create(['name' => 'Test Category']);

        $response = $this->actingAs($user)->delete(
            route('profile.destroy'),
            [
                'password' => 'password',
            ]
        );

        $response->assertRedirect('/');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('tasks', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('categories', ['user_id' => $user->id]);
    }

    #[Test]
    public function account_deletion_invalidates_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete(
            route('profile.destroy'),
            [
                'password' => 'password',
            ]
        );

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    #[Test]
    public function account_deletion_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete(
            route('profile.destroy'),
            [
                'password' => 'wrong-password',
            ]
        );

        $response->assertInvalid('password');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    #[Test]
    public function account_deletion_fails_without_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete(
            route('profile.destroy'),
            [
                'password' => '',
            ]
        );

        $response->assertInvalid('password');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    #[Test]
    public function guest_cannot_delete_account(): void
    {
        $response = $this->delete(
            route('profile.destroy'),
            [
                'password' => 'password',
            ]
        );

        $response->assertRedirect(route('login'));
    }
}
