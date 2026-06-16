<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\RecurringTask;

class RecurringTaskPolicy
{
    /**
     * Determine whether the user can manage the model.
     */
    public function manage(User $user, RecurringTask $recurringTask): bool
    {
        return $recurringTask->user()->is($user);
    }
}
