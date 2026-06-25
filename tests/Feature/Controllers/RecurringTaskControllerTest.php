<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Enums\TaskFrequency;
use App\Models\RecurringTask;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RecurringTaskControllerTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    #[Test]
    public function authenticated_user_can_view_recurring_tasks(): void
    {
        $user = User::factory()->create();
        RecurringTask::factory(3)->for($user)->create();

        $response = $this->actingAs($user)->get(route('recurring-tasks.index'));

        $response->assertOk();
        $response->assertViewIs('recurring-tasks.index');
        $response->assertViewHas('recurringTasks');
    }

    #[Test]
    public function guest_cannot_view_recurring_tasks(): void
    {
        $this->get(route('recurring-tasks.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_create_recurring_task(): void
    {
        $this->post(
            route('recurring-tasks.store'),
            [
                'title'     => 'Daily standup',
                'frequency' => 'daily',
            ]
        )->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_edit_recurring_task(): void
    {
        $recurringTask = RecurringTask::factory()->create();

        $this->get(route('recurring-tasks.edit', $recurringTask))->assertRedirect(route('login'));
    }

    #[Test]
    public function guest_cannot_delete_recurring_task(): void
    {
        $recurringTask = RecurringTask::factory()->create();

        $this->delete(route('recurring-tasks.destroy', $recurringTask))->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_view_create_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('recurring-tasks.create'));

        $response->assertOk();
        $response->assertViewIs('recurring-tasks.create');
        $response->assertViewHas('categories');
        $response->assertViewHas('frequencies');
    }

    #[Test]
    public function user_can_create_a_daily_recurring_task(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('recurring-tasks.store'),
            [
                'title'      => 'Daily standup',
                'frequency'  => TaskFrequency::Daily->value,
                'start_date' => '2026-03-01',
            ]
        );

        $response->assertRedirect(route('recurring-tasks.index'));
        $response->assertSessionHas('success', 'Recurring task created successfully.');
        $this->assertDatabaseHas(
            'recurring_tasks',
            [
                'user_id'   => $user->id,
                'title'     => 'Daily standup',
                'frequency' => TaskFrequency::Daily->value,
            ]
        );
    }

    #[Test]
    public function user_can_create_a_weekly_recurring_task(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('recurring-tasks.store'),
            [
                'title'      => 'Weekly review',
                'frequency'  => TaskFrequency::Weekly->value,
                'days'       => ['monday', 'friday'],
                'start_date' => '2026-03-01',
            ]
        );

        $response->assertRedirect(route('recurring-tasks.index'));
        $this->assertDatabaseHas(
            'recurring_tasks',
            [
                'user_id'   => $user->id,
                'title'     => 'Weekly review',
                'frequency' => TaskFrequency::Weekly->value,
            ]
        );
    }

    #[Test]
    public function user_can_create_a_monthly_recurring_task(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('recurring-tasks.store'),
            [
                'title'        => 'Monthly report',
                'frequency'    => TaskFrequency::Monthly->value,
                'day_of_month' => 15,
                'start_date'   => '2026-03-01',
            ]
        );

        $response->assertRedirect(route('recurring-tasks.index'));
        $this->assertDatabaseHas(
            'recurring_tasks',
            [
                'user_id'   => $user->id,
                'title'     => 'Monthly report',
                'frequency' => TaskFrequency::Monthly->value,
            ]
        );
    }

    #[Test]
    public function user_can_create_a_weekdays_recurring_task(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('recurring-tasks.store'),
            [
                'title'     => 'Weekday check-in',
                'frequency' => TaskFrequency::Weekdays->value,
            ]
        );

        $response->assertRedirect(route('recurring-tasks.index'));
        $this->assertDatabaseHas(
            'recurring_tasks',
            [
                'user_id'   => $user->id,
                'title'     => 'Weekday check-in',
                'frequency' => TaskFrequency::Weekdays->value,
            ]
        );
    }

    #[Test]
    public function user_can_create_recurring_task_with_category(): void
    {
        $user     = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(
            route('recurring-tasks.store'),
            [
                'title'       => 'Categorized task',
                'frequency'   => TaskFrequency::Daily->value,
                'category_id' => $category->uuid,
            ]
        );

        $response->assertRedirect(route('recurring-tasks.index'));
        $this->assertDatabaseHas(
            'recurring_tasks',
            [
                'user_id'     => $user->id,
                'title'       => 'Categorized task',
                'category_id' => $category->id,
            ]
        );
    }

    #[Test]
    public function user_cannot_create_recurring_task_with_another_users_category(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();
        $category  = Category::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)->post(
            route('recurring-tasks.store'),
            [
                'title'       => 'Sneaky task',
                'frequency'   => TaskFrequency::Daily->value,
                'category_id' => $category->uuid,
            ]
        );

        $response->assertInvalid('category_id');
    }

    public static function invalidRecurringTaskDataProvider(): array
    {
        return [
            'missing title'                      => [
                ['title' => '', 'frequency' => 'daily'],
                'title',
            ],
            'title too long'                     => [
                ['title' => str_repeat('a', 256), 'frequency' => 'daily'],
                'title',
            ],
            'missing frequency'                  => [
                ['title' => 'Valid title', 'frequency' => ''],
                'frequency',
            ],
            'invalid frequency'                  => [
                ['title' => 'Valid title', 'frequency' => 'biweekly'],
                'frequency',
            ],
            'weekly without days'                => [
                ['title' => 'Valid title', 'frequency' => 'weekly'],
                'days',
            ],
            'weekly with invalid day'            => [
                ['title' => 'Valid title', 'frequency' => 'weekly', 'days' => ['funday']],
                'days.0',
            ],
            'monthly without day_of_month'       => [
                ['title' => 'Valid title', 'frequency' => 'monthly'],
                'day_of_month',
            ],
            'monthly with day_of_month too high' => [
                ['title' => 'Valid title', 'frequency' => 'monthly', 'day_of_month' => 32],
                'day_of_month',
            ],
            'monthly with day_of_month too low'  => [
                ['title' => 'Valid title', 'frequency' => 'monthly', 'day_of_month' => 0],
                'day_of_month',
            ],
            'end_date before start_date'         => [
                [
                    'title'      => 'Valid title',
                    'frequency'  => 'daily',
                    'start_date' => '2026-06-01',
                    'end_date'   => '2026-01-01',
                ],
                'end_date',
            ],
        ];
    }

    #[Test]
    #[DataProvider('invalidRecurringTaskDataProvider')]
    public function recurring_task_creation_fails_with_invalid_data(array $data, string $expectedErrorField): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('recurring-tasks.store'), $data);

        $response->assertInvalid($expectedErrorField);
    }

    #[Test]
    public function authenticated_user_can_view_edit_form(): void
    {
        $user          = User::factory()->create();
        $recurringTask = RecurringTask::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('recurring-tasks.edit', $recurringTask));

        $response->assertOk();
        $response->assertViewIs('recurring-tasks.edit');
        $response->assertViewHas('recurringTask');
        $response->assertViewHas('categories');
        $response->assertViewHas('frequencies');
    }

    #[Test]
    public function user_can_update_their_own_recurring_task(): void
    {
        $user          = User::factory()->create();
        $recurringTask = RecurringTask::factory()->daily()->for($user)->create();

        $response = $this->actingAs($user)->put(
            route('recurring-tasks.update', $recurringTask),
            [
                'title'     => 'Updated recurring task',
                'frequency' => TaskFrequency::Daily->value,
            ]
        );

        $response->assertRedirect(route('recurring-tasks.index'));
        $response->assertSessionHas('success', 'Recurring task updated successfully.');
        $this->assertDatabaseHas(
            'recurring_tasks',
            [
                'id'    => $recurringTask->id,
                'title' => 'Updated recurring task',
            ]
        );
    }

    #[Test]
    #[DataProvider('invalidRecurringTaskDataProvider')]
    public function recurring_task_update_fails_with_invalid_data(array $data, string $expectedErrorField): void
    {
        $user          = User::factory()->create();
        $recurringTask = RecurringTask::factory()->daily()->for($user)->create();

        $response = $this->actingAs($user)->put(route('recurring-tasks.update', $recurringTask), $data);

        $response->assertInvalid($expectedErrorField);
    }

    #[Test]
    public function user_can_delete_their_own_recurring_task(): void
    {
        $user          = User::factory()->create();
        $recurringTask = RecurringTask::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete(route('recurring-tasks.destroy', $recurringTask));

        $response->assertNoContent();
        $this->assertSoftDeleted('recurring_tasks', ['id' => $recurringTask->id]);
    }

    #[Test]
    public function user_cannot_edit_another_users_recurring_task(): void
    {
        $owner         = User::factory()->create();
        $otherUser     = User::factory()->create();
        $recurringTask = RecurringTask::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->get(route('recurring-tasks.edit', $recurringTask));

        $response->assertForbidden();
    }

    #[Test]
    public function user_cannot_update_another_users_recurring_task(): void
    {
        $owner         = User::factory()->create();
        $otherUser     = User::factory()->create();
        $recurringTask = RecurringTask::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->put(
            route('recurring-tasks.update', $recurringTask),
            [
                'title'     => 'Hacked title',
                'frequency' => TaskFrequency::Daily->value,
            ]
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('recurring_tasks', ['title' => 'Hacked title']);
    }

    #[Test]
    public function user_cannot_delete_another_users_recurring_task(): void
    {
        $owner         = User::factory()->create();
        $otherUser     = User::factory()->create();
        $recurringTask = RecurringTask::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->delete(route('recurring-tasks.destroy', $recurringTask));

        $response->assertForbidden();
        $this->assertDatabaseHas('recurring_tasks', ['id' => $recurringTask->id]);
    }

    #[Test]
    public function user_only_sees_their_own_recurring_tasks(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();

        RecurringTask::factory(2)->for($user)->create();
        RecurringTask::factory(3)->for($otherUser)->create();

        $response = $this->actingAs($user)->get(route('recurring-tasks.index'));

        $response->assertOk();
        $this->assertCount(2, $response->viewData('recurringTasks'));
    }

    #[Test]
    public function user_can_create_recurring_task_with_start_and_end_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('recurring-tasks.store'),
            [
                'title'      => 'Temporary task',
                'frequency'  => TaskFrequency::Daily->value,
                'start_date' => '2026-03-01',
                'end_date'   => '2026-06-01',
            ]
        );

        $response->assertRedirect(route('recurring-tasks.index'));
        $this->assertDatabaseHas(
            'recurring_tasks',
            [
                'user_id' => $user->id,
                'title'   => 'Temporary task',
            ]
        );
    }
}
