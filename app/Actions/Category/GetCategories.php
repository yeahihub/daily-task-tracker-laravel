<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Category;
use App\Services\CategoryCacheService;

readonly class GetCategories
{
    public function __construct(private CategoryCacheService $categoryCacheService)
    {
    }

    public function execute(int $userId): array
    {
        return $this->categoryCacheService->remember($userId, fn() => Category::query()
            ->where('user_id', $userId)
            ->orderBy('name')
            ->pluck('name', 'uuid')
            ->toArray()
        );
    }
}
