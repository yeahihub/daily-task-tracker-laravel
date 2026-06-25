<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DashboardControllerTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard');
        $response->assertViewHas('stats');
        $response->assertViewHas('upcomingTasks');
        $response->assertViewHas('overdueTasks');
        $response->assertViewHas('recentCompletions');
        $response->assertViewHas('today');
    }

    #[Test]
    public function guest_cannot_view_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    #[Test]
    public function unverified_user_cannot_view_dashboard(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('verification.notice'));
    }

    #[Test]
    public function dashboard_stats_reflect_todays_tasks(): void
    {
        $user = User::factory()->create();

        Task::factory(3)->today()->for($user)->create();
        Task::factory()->today()->completed()->for($user)->create(['task_date' => now()->startOfDay()]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();

        $stats = $response->viewData('stats');
        $this->assertEquals(4, $stats['tasks_today']);
        $this->assertEquals(1, $stats['completed_today']);
    }

    #[Test]
    public function dashboard_shows_overdue_tasks(): void
    {
        $user = User::factory()->create();

        Task::factory(2)->overdue()->for($user)->create();
        Task::factory()->today()->for($user)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();

        $stats = $response->viewData('stats');
        $this->assertEquals(2, $stats['overdue']);
    }

    #[Test]
    public function dashboard_shows_upcoming_tasks(): void
    {
        $user = User::factory()->create();

        Task::factory()->for($user)->create(['task_date' => now()->startOfDay(), 'completed_at' => null]);
        Task::factory()->for($user)->create(['task_date' => now()->addDay()->startOfDay(), 'completed_at' => null]);
        Task::factory()->for($user)->create(['task_date' => now()->addDays(5)->startOfDay(), 'completed_at' => null]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertCount(2, $response->viewData('upcomingTasks'));
    }

    #[Test]
    public function dashboard_shows_recent_completions_count(): void
    {
        $user = User::factory()->create();

        Task::factory(3)->for($user)->create(
            [
                'task_date'    => now()->subDays(2)->startOfDay(),
                'completed_at' => now()->subDay(),
            ]
        );

        Task::factory()->for($user)->create(
            [
                'task_date'    => now()->subDays(10)->startOfDay(),
                'completed_at' => now()->subDays(10),
            ]
        );

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertEquals(3, $response->viewData('recentCompletions'));
    }

    #[Test]
    public function dashboard_completion_rate_is_zero_when_no_tasks_today(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $stats = $response->viewData('stats');
        $this->assertEquals(0, $stats['today_completion_rate']);
    }

    #[Test]
    public function dashboard_does_not_show_other_users_data(): void
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();

        Task::factory(3)->today()->for($otherUser)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $stats = $response->viewData('stats');
        $this->assertEquals(0, $stats['tasks_today']);
    }
}
