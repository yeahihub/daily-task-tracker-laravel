<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Models\User;
use App\Models\RecurringTask;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ArchiveExpiredRecurringTasksTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_soft_deletes_recurring_tasks_past_their_end_date(): void
    {
        $user        = User::factory()->create();
        $expiredTask = RecurringTask::factory()->for($user)->create(
            [
                'end_date' => now()->subDay(),
            ]
        );

        $this->artisan('app:archive-expired-recurring-tasks')
            ->expectsOutputToContain('Archived 1 expired recurring tasks.');

        $this->assertSoftDeleted('recurring_tasks', ['id' => $expiredTask->id]);
    }

    #[Test]
    public function it_does_not_delete_recurring_tasks_with_future_end_date(): void
    {
        $user       = User::factory()->create();
        $activeTask = RecurringTask::factory()->for($user)->create(
            [
                'end_date' => now()->addMonth(),
            ]
        );

        $this->artisan('app:archive-expired-recurring-tasks')
            ->expectsOutputToContain('There are no expired recurring tasks to archive.');

        $this->assertDatabaseHas('recurring_tasks', ['id' => $activeTask->id]);
        $this->assertNull($activeTask->fresh()->deleted_at);
    }

    #[Test]
    public function it_does_not_delete_recurring_tasks_without_end_date(): void
    {
        $user          = User::factory()->create();
        $openEndedTask = RecurringTask::factory()->for($user)->create(
            [
                'end_date' => null,
            ]
        );

        $this->artisan('app:archive-expired-recurring-tasks')
            ->expectsOutputToContain('There are no expired recurring tasks to archive.');

        $this->assertDatabaseHas('recurring_tasks', ['id' => $openEndedTask->id]);
    }

    #[Test]
    public function it_does_not_delete_recurring_tasks_ending_today(): void
    {
        $user          = User::factory()->create();
        $endsTodayTask = RecurringTask::factory()->for($user)->create(
            [
                'end_date' => today(),
            ]
        );

        $this->artisan('app:archive-expired-recurring-tasks')
            ->expectsOutputToContain('There are no expired recurring tasks to archive.');

        $this->assertDatabaseHas('recurring_tasks', ['id' => $endsTodayTask->id]);
        $this->assertNull($endsTodayTask->fresh()->deleted_at);
    }

    #[Test]
    public function it_archives_multiple_expired_tasks(): void
    {
        $user = User::factory()->create();
        RecurringTask::factory(3)->for($user)->create(
            [
                'end_date' => now()->subDays(5),
            ]
        );

        $this->artisan('app:archive-expired-recurring-tasks')
            ->expectsOutputToContain('Archived 3 expired recurring tasks.');
    }
}
