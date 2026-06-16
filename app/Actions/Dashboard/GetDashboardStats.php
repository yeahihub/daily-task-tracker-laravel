<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\User;
use Carbon\CarbonInterface;

class GetDashboardStats
{
    /**
     * @return array{
     *     tasks_today: int,
     *     completed_today: int,
     *     overdue: int,
     *     total_pending: int,
     *     today_completion_rate: int
     * }
     */
    public function execute(User $user, CarbonInterface $today): array
    {
        $todayDate = $today->toDateString();

        $stats = $user->tasks()
            ->toBase()
            ->selectRaw(
                'COUNT(CASE WHEN task_date = ? THEN 1 END) as tasks_today,
                COUNT(CASE WHEN task_date = ? AND completed_at IS NOT NULL THEN 1 END) as completed_today,
                COUNT(CASE WHEN task_date < ? AND completed_at IS NULL THEN 1 END) as overdue,
                COUNT(CASE WHEN completed_at IS NULL THEN 1 END) as total_pending',
                [$todayDate, $todayDate, $todayDate]
            )
            ->first();

        return [
            'tasks_today'           => (int) $stats->tasks_today,
            'completed_today'       => (int) $stats->completed_today,
            'overdue'               => (int) $stats->overdue,
            'total_pending'         => (int) $stats->total_pending,
            'today_completion_rate' => $stats->tasks_today > 0
                ? (int) round(($stats->completed_today / $stats->tasks_today) * 100)
                : 0,
        ];
    }
}
