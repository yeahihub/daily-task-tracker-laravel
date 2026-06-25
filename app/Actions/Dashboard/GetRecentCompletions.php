<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\User;
use Carbon\CarbonInterface;

class GetRecentCompletions
{
    /**
     * Get count of tasks completed in the last N days.
     */
    public function execute(User $user, CarbonInterface $today, int $days = 7): int
    {
        return $user->tasks()
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $today->copy()->subDays($days))
            ->count();
    }
}
