<?php

declare(strict_types=1);

namespace App\Actions\Task;

use App\Models\Task;

class ToggleTaskCompletion
{
    public function execute(Task $task): bool
    {
        $task->completed_at = $task->completed_at ? null : now();
        $task->save();

        return $task->completed_at !== null;
    }
}
