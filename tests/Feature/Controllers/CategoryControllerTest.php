<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CategoryControllerTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    #[Test]
    public function authenticated_user_can_view_categories(): void
    {
        $user = User::factory()->create();
        Category::factory(3)->for($user)->create();

        $response = $this->actingAs($user)->get(route('categories.index'));

        $response->assertOk();
        $response->assertViewIs('categories.index');
        $response->assertViewHas('categories');
    }

    #[Test]
    public function guest_cannot_view_categories(): void
    {
        $this->get(route('categories.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_create_category(): void
    {
        $this->post(route('categories.store'), ['name' => 'Work'])->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_edit_category(): void
    {
        $category = Category::factory()->create();

        $this->get(route('categories.edit', $category))->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_delete_category(): void
    {
        $category = Category::factory()->create();

        $this->delete(route('categories.destroy', $category))->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_view_create_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('categories.create'));

        $response->assertOk();
        $response->assertViewIs('categories.create');
    }

    #[Test]
    public function authenticated_user_can_create_a_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('categories.store'),
            [
                'name' => 'Work',
            ]
        );

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('success', 'Category created successfully.');
        $this->assertDatabaseHas(
            'categories',
            [
                'user_id' => $user->id,
                'name'    => 'Work',
            ]
        );
    }

    public static function invalidCategoryDataProvider(): array
    {
        return [
            'missing name'  => [
                ['name' => ''],
                'name',
            ],
            'name too long' => [
                ['name' => str_repeat('a', 256)],
                'name',
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidCategoryDataProvider')]
    public function category_creation_fails_with_invalid_data(array $data, string $expectedErrorField): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('categories.store'), $data);

        $response->assertInvalid($expectedErrorField);
    }

    #[Test]
    public function authenticated_user_can_view_edit_form(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('categories.edit', $category));

        $response->assertOk();
        $response->assertViewIs('categories.edit');
        $response->assertViewHas('category');
    }

    #[Test]
    public function user_can_update_their_own_category(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->for($user)->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->put(
            route('categories.update', $category),
            [
                'name' => 'New Name',
            ]
        );

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('success', 'Category updated successfully.');
        $this->assertDatabaseHas(
            'categories',
            [
                'id'   => $category->id,
                'name' => 'New Name',
            ]
        );
    }

    #[Test]
    #[DataProvider('invalidCategoryDataProvider')]
    public function category_update_fails_with_invalid_data(array $data, string $expectedErrorField): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $response = $this->actingAs($user)->put(route('categories.update', $category), $data);

        $response->assertInvalid($expectedErrorField);
    }

    #[Test]
    public function user_can_delete_their_own_category(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete(route('categories.destroy', $category));

        $response->assertNoContent();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    #[Test]
    public function user_cannot_edit_another_users_category(): void
    {
        $owner     = User::factory()->create();
        $otherUser = User::factory()->create();
        $category  = Category::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->get(route('categories.edit', $category));

        $response->assertForbidden();
    }

    #[Test]
    public function user_cannot_update_another_users_category(): void
    {
        $owner     = User::factory()->create();
        $otherUser = User::factory()->create();
        $category  = Category::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->put(
            route('categories.update', $category),
            [
                'name' => 'Hacked Name',
            ]
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('categories', ['name' => 'Hacked Name']);
    }

    #[Test]
    public function user_cannot_delete_another_users_category(): void
    {
        $owner     = User::factory()->create();
        $otherUser = User::factory()->create();
        $category  = Category::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->delete(route('categories.destroy', $category));

        $response->assertForbidden();
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    #[Test]
    public function user_only_sees_their_own_categories(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();

        Category::factory(2)->for($user)->create();
        Category::factory(3)->for($otherUser)->create();

        $response = $this->actingAs($user)->get(route('categories.index'));

        $response->assertOk();
        $this->assertCount(2, $response->viewData('categories'));
    }
}
