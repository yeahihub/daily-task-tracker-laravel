<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\User;
use Carbon\CarbonInterface;

class GetOverdueTasks
{
    /**
     * Get overdue incomplete tasks.
     */
    public function execute(User $user, CarbonInterface $today, int $limit = 5): array
    {
        return $user->tasks()
            ->with('category')
            ->whereNull('completed_at')
            ->whereDate('task_date', '<', $today)
            ->orderBy('task_date')
            ->orderBy('created_at')
            ->limit($limit)
            ->get()
            ->toResourceCollection()
            ->resolve();
    }
}
