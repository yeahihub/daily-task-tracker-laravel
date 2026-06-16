<?php

declare(strict_types=1);

namespace App\Actions\RecurringTask;

use App\Models\RecurringTask;

class DeleteRecurringTask
{
    public function execute(RecurringTask $recurringTask): void
    {
        $recurringTask->delete();
    }
}
