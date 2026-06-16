<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Category;
use App\Services\CategoryCacheService;

readonly class CategoryObserver
{
    public function __construct(private CategoryCacheService $categoryCacheService)
    {
    }

    public function created(Category $category): void
    {
        $this->categoryCacheService->clear($category->user_id);
    }

    public function updated(Category $category): void
    {
        $this->categoryCacheService->clear($category->user_id);
    }

    public function deleted(Category $category): void
    {
        $this->categoryCacheService->clear($category->user_id);
    }
}
