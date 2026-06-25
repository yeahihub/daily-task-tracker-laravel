<?php

declare(strict_types=1);

namespace App\Actions\Task;

use App\Models\Task;
use App\Models\User;
use App\Actions\Category\ResolveCategory;

readonly class UpdateTask
{
    public function __construct(
        private ResolveCategory $resolveCategory,
    ) {
    }

    public function execute(Task $task, array $data, User $user): Task
    {
        $data['category_id'] = $this->resolveCategory->execute($data['category_id'] ?? null, $user);

        $task->fill($data);
        $task->save();

        return $task;
    }
}
