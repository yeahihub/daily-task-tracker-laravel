<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use App\Models\RecurringTask;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GenerateRecurringTasksTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_generates_daily_recurring_tasks(): void
    {
        $user = User::factory()->create();
        RecurringTask::factory()->daily()->for($user)->create(['title' => 'Daily standup']);

        $this->artisan('app:generate-recurring-tasks')
            ->assertSuccessful();

        $this->assertDatabaseHas(
            'tasks',
            [
                'user_id'   => $user->id,
                'title'     => 'Daily standup',
                'task_date' => today()->toDateString(),
            ]
        );
    }

    #[Test]
    public function it_generates_weekday_recurring_tasks_on_weekdays(): void
    {
        $this->travelTo(now()->next('Monday'));

        $user = User::factory()->create();
        RecurringTask::factory()->weekdays()->for($user)->create(['title' => 'Weekday task']);

        $this->artisan('app:generate-recurring-tasks')
            ->assertSuccessful();

        $this->assertDatabaseHas(
            'tasks',
            [
                'user_id' => $user->id,
                'title'   => 'Weekday task',
            ]
        );
    }

    #[Test]
    public function it_skips_weekday_recurring_tasks_on_weekends(): void
    {
        $this->travelTo(now()->next('Saturday'));

        $user = User::factory()->create();
        RecurringTask::factory()->weekdays()->for($user)->create(['title' => 'Weekday only']);

        $this->artisan('app:generate-recurring-tasks');

        $this->assertDatabaseMissing(
            'tasks',
            [
                'user_id' => $user->id,
                'title'   => 'Weekday only',
            ]
        );
    }

    #[Test]
    public function it_generates_weekly_recurring_tasks_on_matching_day(): void
    {
        $this->travelTo(now()->next('Wednesday'));

        $user = User::factory()->create();
        RecurringTask::factory()->weekly(['wednesday'])->for($user)->create(['title' => 'Wednesday task']);

        $this->artisan('app:generate-recurring-tasks')
            ->assertSuccessful();

        $this->assertDatabaseHas(
            'tasks',
            [
                'user_id' => $user->id,
                'title'   => 'Wednesday task',
            ]
        );
    }

    #[Test]
    public function it_skips_weekly_recurring_tasks_on_non_matching_day(): void
    {
        $this->travelTo(now()->next('Thursday'));

        $user = User::factory()->create();
        RecurringTask::factory()->weekly(['monday'])->for($user)->create(['title' => 'Monday only']);

        $this->artisan('app:generate-recurring-tasks');

        $this->assertDatabaseMissing(
            'tasks',
            [
                'user_id' => $user->id,
                'title'   => 'Monday only',
            ]
        );
    }

    #[Test]
    public function it_generates_monthly_recurring_tasks_on_matching_day(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(14));

        $user = User::factory()->create();
        RecurringTask::factory()->monthly(15)->for($user)->create(['title' => 'Monthly report']);

        $this->artisan('app:generate-recurring-tasks')
            ->assertSuccessful();

        $this->assertDatabaseHas(
            'tasks',
            [
                'user_id' => $user->id,
                'title'   => 'Monthly report',
            ]
        );
    }

    #[Test]
    public function it_skips_monthly_recurring_tasks_on_non_matching_day(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(9));

        $user = User::factory()->create();
        RecurringTask::factory()->monthly(15)->for($user)->create(['title' => 'Monthly report']);

        $this->artisan('app:generate-recurring-tasks');

        $this->assertDatabaseMissing(
            'tasks',
            [
                'user_id' => $user->id,
                'title'   => 'Monthly report',
            ]
        );
    }

    #[Test]
    public function it_does_not_duplicate_tasks_already_generated_today(): void
    {
        $user          = User::factory()->create();
        $recurringTask = RecurringTask::factory()->daily()->for($user)->create(['title' => 'Daily standup']);

        Task::factory()->forRecurringTask($recurringTask)->for($user)->create(
            [
                'task_date' => today(),
            ]
        );

        $this->artisan('app:generate-recurring-tasks');

        $this->assertDatabaseCount('tasks', 1);
    }

    #[Test]
    public function it_skips_recurring_tasks_past_their_end_date(): void
    {
        $user = User::factory()->create();
        RecurringTask::factory()->daily()->for($user)->create(
            [
                'title'    => 'Expired task',
                'end_date' => now()->subDay(),
            ]
        );

        $this->artisan('app:generate-recurring-tasks');

        $this->assertDatabaseMissing(
            'tasks',
            [
                'user_id' => $user->id,
                'title'   => 'Expired task',
            ]
        );
    }

    #[Test]
    public function it_skips_recurring_tasks_before_their_start_date(): void
    {
        $user = User::factory()->create();
        RecurringTask::factory()->daily()->for($user)->create(
            [
                'title'      => 'Future task',
                'start_date' => now()->addWeek(),
            ]
        );

        $this->artisan('app:generate-recurring-tasks');

        $this->assertDatabaseMissing(
            'tasks',
            [
                'user_id' => $user->id,
                'title'   => 'Future task',
            ]
        );
    }

    #[Test]
    public function it_outputs_failure_when_no_active_recurring_tasks(): void
    {
        $this->artisan('app:generate-recurring-tasks')
            ->expectsOutput('No active recurring tasks found.')
            ->assertFailed();
    }

    #[Test]
    public function it_outputs_counts_of_created_and_skipped_tasks(): void
    {
        $this->travelTo(now()->next('Monday'));

        $user = User::factory()->create();
        RecurringTask::factory()->daily()->for($user)->create();
        RecurringTask::factory()->weekly(['saturday'])->for($user)->create();

        $this->artisan('app:generate-recurring-tasks')
            ->expectsOutputToContain('Created 1 recurring tasks.')
            ->expectsOutputToContain('Skipped 1 recurring tasks.')
            ->assertSuccessful();
    }

    #[Test]
    public function it_copies_category_and_description_from_recurring_task(): void
    {
        $user          = User::factory()->create();
        $recurringTask = RecurringTask::factory()->daily()->for($user)->create(
            [
                'title'       => 'Detailed task',
                'description' => 'Important description',
            ]
        );

        $this->artisan('app:generate-recurring-tasks')
            ->assertSuccessful();

        $this->assertDatabaseHas(
            'tasks',
            [
                'user_id'           => $user->id,
                'title'             => 'Detailed task',
                'description'       => 'Important description',
                'category_id'       => $recurringTask->category_id,
                'recurring_task_id' => $recurringTask->id,
            ]
        );
    }
}
