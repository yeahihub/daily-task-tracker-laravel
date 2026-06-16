<?php

declare(strict_types=1);

namespace App\Actions\RecurringTask;

use App\Models\User;
use App\Enums\TaskFrequency;
use App\Models\RecurringTask;
use App\Actions\Category\ResolveCategory;

readonly class CreateRecurringTask
{
    public function __construct(
        private ResolveCategory $resolveCategory,
    ) {
    }

    public function execute(array $data, User $user): RecurringTask
    {
        $data['category_id']      = $this->resolveCategory->execute($data['category_id'] ?? null, $user);
        $data['frequency_config'] = TaskFrequency::from($data['frequency'])->buildConfig($data);

        unset($data['days'], $data['day_of_month']);

        /** @var RecurringTask */
        return $user->recurringTasks()->create($data);
    }
}
