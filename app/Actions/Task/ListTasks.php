<?php

declare(strict_types=1);

namespace App\Actions\Task;

use App\Models\User;
use App\Enums\TaskStatus;
use App\Actions\Category\ResolveCategory;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator;

readonly class ListTasks
{
    public function __construct(
        private ResolveCategory $resolveCategory,
    ) {
    }

    /**
     * @param array{status?: string, category_id?: string, date_from?: string, date_to?: string} $filters
     *
     * @throws ValidationException
     */
    public function execute(User $user, array $filters): LengthAwarePaginator
    {
        $categoryId = $this->resolveCategory->execute($filters['category_id'] ?? null, $user);

        return $user->tasks()->with('category')
            ->when(($filters['status'] ?? null) === TaskStatus::Completed->value, fn($query) => $query->whereNotNull('completed_at'))
            ->when(($filters['status'] ?? null) === TaskStatus::Incomplete->value, fn($query) => $query->whereNull('completed_at'))
            ->when($categoryId, fn($query) => $query->where('category_id', $categoryId))
            ->when($filters['date_from'] ?? null, fn($query) => $query->whereDate('task_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] ?? null, fn($query) => $query->whereDate('task_date', '<=', $filters['date_to']))
            ->latest()
            ->paginate();
    }
}
