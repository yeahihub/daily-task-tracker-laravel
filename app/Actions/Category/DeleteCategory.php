<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Category;

readonly class DeleteCategory
{
    public function execute(Category $category): void
    {
        $category->delete();
    }
}
