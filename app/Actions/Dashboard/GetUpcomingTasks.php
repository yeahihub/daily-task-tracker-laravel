<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\User;
use Carbon\CarbonInterface;

class GetUpcomingTasks
{
    /**
     * Get upcoming incomplete tasks for today and tomorrow.
     */
    public function execute(User $user, CarbonInterface $today, CarbonInterface $tomorrow, int $limit = 10): array
    {
        return $user->tasks()
            ->with('category')
            ->whereNull('completed_at')
            ->whereBetween('task_date', [$today, $tomorrow])
            ->orderBy('task_date')
            ->orderBy('created_at')
            ->limit($limit)
            ->get()
            ->toResourceCollection()
            ->resolve();
    }
}
