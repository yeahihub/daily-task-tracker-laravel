<?php

declare(strict_types=1);

namespace App\Actions\RecurringTask;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ListRecurringTasks
{
    public function execute(User $user): LengthAwarePaginator
    {
        return $user->recurringTasks()
            ->with('category')
            ->latest()
            ->paginate();
    }
}
