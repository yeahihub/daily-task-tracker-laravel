<?php

declare(strict_types=1);

namespace App\Actions\Task;

use App\Models\Task;
use App\Models\User;
use App\Actions\Category\ResolveCategory;

readonly class CreateTask
{
    public function __construct(private ResolveCategory $resolveCategory)
    {
        //
    }

    public function execute(array $taskData, User $user): Task
    {
        $taskData['category_id'] = $this->resolveCategory->execute($taskData['category_id'] ?? null, $user);

        /** @var Task */
        return $user->tasks()->create($taskData);
    }
}
