<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use App\Models\Category;
use App\Enums\TaskStatus;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TaskControllerTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    #[Test]
    public function authenticated_user_can_view_tasks(): void
    {
        // Arrange
        $user = User::factory()->create();

        Task::factory(3)->for($user)->create();

        // Act
        $response = $this->actingAs($user)->get(route('tasks.index'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('tasks.index');
        $response->assertViewHas('tasks');
    }

    #[Test]
    public function guest_cannot_view_tasks(): void
    {
        $this->get(route('tasks.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_create_tasks(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $taskData = [
            'title'       => $this->faker->words(asText: true),
            'category_id' => $category->uuid,
            'description' => $this->faker->sentence(),
            'task_date'   => $this->faker->date(),
        ];

        $response = $this->actingAs($user)->post(route('tasks.store'), $taskData);

        $response->assertRedirect(route('tasks.index'));
        $response->assertSessionHas('success', 'Task created successfully.');

        $this->assertDatabaseHas(
            'tasks',
            [
                'user_id' => $user->id,
                'title'   => $taskData['title'],
            ]
        );
    }

    public static function invalidTaskDataProvider(): array
    {
        return [
            'missing title'     => [
                ['title' => '', 'task_date' => fake()->date()],
                'title',
            ],
            'title too long'    => [
                ['title' => str_repeat('a', 256), 'task_date' => '2026-02-25'],
                'title',
            ],
            'missing task date' => [
                ['title' => 'Valid title', 'task_date' => ''],
                'task_date',
            ],
            'invalid task date' => [
                ['title' => 'Valid title', 'task_date' => 'not-a-date'],
                'task_date',
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidTaskDataProvider')]
    public function task_creation_fails_with_invalid_data(array $data, string $expectedErrorField): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tasks.store'), $data);

        $response->assertInvalid($expectedErrorField);
        $this->assertDatabaseCount('tasks', 0);
    }

    #[Test]
    public function user_cannot_edit_another_users_task(): void
    {
        $owner     = User::factory()->create();
        $otherUser = User::factory()->create();
        $task      = Task::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->get(route('tasks.edit', $task));

        $response->assertForbidden();
    }

    #[Test]
    public function user_cannot_delete_another_users_task(): void
    {
        $owner     = User::factory()->create();
        $otherUser = User::factory()->create();
        $task      = Task::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->delete(route('tasks.destroy', $task));

        $response->assertForbidden();
    }

    #[Test]
    public function user_cannot_create_a_task_with_another_users_category(): void
    {
        $owner     = User::factory()->create();
        $category  = Category::factory()->for($owner)->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->post(
            route('tasks.store'),
            [
                'title'       => $this->faker->words(asText: true),
                'category_id' => $category->uuid,
                'task_date'   => $this->faker->date(),
            ]
        );

        $response->assertInvalid('category_id');
        $this->assertDatabaseCount('tasks', 0);
    }

    #[Test]
    public function user_can_toggle_task_completion(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $this->assertNull($task->completed_at);

        $response = $this->actingAs($user)->patch(route('tasks.toggle-completion', $task));

        $response->assertOk();
        $response->assertJson(['completed' => true]);
        $this->assertNotNull($task->fresh()->completed_at);

        $response = $this->actingAs($user)->patch(route('tasks.toggle-completion', $task));

        $response->assertOk();
        $response->assertJson(['completed' => false]);
        $this->assertNull($task->fresh()->completed_at);
    }

    #[Test]
    public function user_can_delete_a_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete(route('tasks.destroy', $task));

        $response->assertNoContent();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    #[Test]
    public function task_index_can_filter_by_completed_status(): void
    {
        $user = User::factory()->create();

        Task::factory(3)->for($user)->create();
        Task::factory()->count(2)->completed()->for($user)->create();

        $response = $this->actingAs($user)->get(route('tasks.index', ['status' => TaskStatus::Completed->value]));

        $response->assertOk();
        $this->assertCount(2, $response->viewData('tasks'));
    }

    #[Test]
    public function categories_are_cached_when_user_visits_the_index_page(): void
    {
        $user = User::factory()->create();

        Category::factory(3)->for($user)->create();

        Cache::expects('remember')
            ->withSomeOfArgs('categories.user.' . $user->id, 3600)
            ->andReturnUsing(fn($key, $ttl, $callback) => $callback());

        $resposne = $this->actingAs($user)->get(route('tasks.index'));

        $resposne->assertOk();
    }

    #[Test]
    public function task_is_overdue_when_date_is_in_the_past(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create(
            ['task_date' => now()->addDays(2)]
        );

        $this->assertFalse($task->task_date->isPast());

        $this->travel(3)->days();

        $this->assertTrue($task->task_date->isPast());
    }

    #[Test]
    public function guest_cannot_create_tasks(): void
    {
        $this->post(
            route('tasks.store'),
            [
                'title'     => 'A task',
                'task_date' => '2026-03-05',
            ]
        )->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_edit_tasks(): void
    {
        $task = Task::factory()->create();

        $this->get(route('tasks.edit', $task))->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_delete_tasks(): void
    {
        $task = Task::factory()->create();

        $this->delete(route('tasks.destroy', $task))->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_toggle_task_completion(): void
    {
        $task = Task::factory()->create();

        $this->patch(route('tasks.toggle-completion', $task))->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_view_create_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('tasks.create'));

        $response->assertOk();
        $response->assertViewIs('tasks.create');
        $response->assertViewHas('categories');
    }

    #[Test]
    public function authenticated_user_can_view_edit_form(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('tasks.edit', $task));

        $response->assertOk();
        $response->assertViewIs('tasks.edit');
        $response->assertViewHas('task');
        $response->assertViewHas('categories');
    }

    #[Test]
    public function user_can_create_task_without_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('tasks.store'),
            [
                'title'     => 'Task without category',
                'task_date' => '2026-03-10',
            ]
        );

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas(
            'tasks',
            [
                'user_id'     => $user->id,
                'title'       => 'Task without category',
                'category_id' => null,
            ]
        );
    }

    #[Test]
    public function user_can_update_their_own_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $response = $this->actingAs($user)->put(
            route('tasks.update', $task),
            [
                'title'     => 'Updated title',
                'task_date' => '2026-04-01',
            ]
        );

        $response->assertRedirect(route('tasks.index'));
        $response->assertSessionHas('success', 'Task updated successfully.');
        $this->assertDatabaseHas(
            'tasks',
            [
                'id'    => $task->id,
                'title' => 'Updated title',
            ]
        );
    }

    #[Test]
    public function user_cannot_update_another_users_task(): void
    {
        $owner     = User::factory()->create();
        $otherUser = User::factory()->create();
        $task      = Task::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->put(
            route('tasks.update', $task),
            [
                'title'     => 'Hacked title',
                'task_date' => '2026-04-01',
            ]
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('tasks', ['title' => 'Hacked title']);
    }

    #[Test]
    public function user_cannot_toggle_another_users_task(): void
    {
        $owner     = User::factory()->create();
        $otherUser = User::factory()->create();
        $task      = Task::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->patch(route('tasks.toggle-completion', $task));

        $response->assertForbidden();
    }

    #[Test]
    #[DataProvider('invalidTaskDataProvider')]
    public function task_update_fails_with_invalid_data(array $data, string $expectedErrorField): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $response = $this->actingAs($user)->put(route('tasks.update', $task), $data);

        $response->assertInvalid($expectedErrorField);
    }

    #[Test]
    public function task_index_can_filter_by_incomplete_status(): void
    {
        $user = User::factory()->create();

        Task::factory(3)->for($user)->create();
        Task::factory(2)->completed()->for($user)->create();

        $response = $this->actingAs($user)->get(route('tasks.index', ['status' => TaskStatus::Incomplete->value]));

        $response->assertOk();
        $this->assertCount(3, $response->viewData('tasks'));
    }

    #[Test]
    public function task_index_can_filter_by_category(): void
    {
        $user          = User::factory()->create();
        $category      = Category::factory()->for($user)->create();
        $otherCategory = Category::factory()->for($user)->create();

        Task::factory(2)->for($user)->create(['category_id' => $category->id]);
        Task::factory(3)->for($user)->create(['category_id' => $otherCategory->id]);

        $response = $this->actingAs($user)->get(route('tasks.index', ['category_id' => $category->uuid]));

        $response->assertOk();
        $this->assertCount(2, $response->viewData('tasks'));
    }

    #[Test]
    public function task_index_can_filter_by_date_range(): void
    {
        $user = User::factory()->create();

        Task::factory()->for($user)->create(['task_date' => '2026-03-01']);
        Task::factory()->for($user)->create(['task_date' => '2026-03-05']);
        Task::factory()->for($user)->create(['task_date' => '2026-03-10']);

        $response = $this->actingAs($user)->get(
            route(
                'tasks.index',
                [
                    'date_from' => '2026-03-01',
                    'date_to'   => '2026-03-05',
                ]
            )
        );

        $response->assertOk();
        $this->assertCount(2, $response->viewData('tasks'));
    }

    #[Test]
    public function user_only_sees_their_own_tasks(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();

        Task::factory(2)->for($user)->create();
        Task::factory(3)->for($otherUser)->create();

        $response = $this->actingAs($user)->get(route('tasks.index'));

        $response->assertOk();
        $this->assertCount(2, $response->viewData('tasks'));
    }
}
