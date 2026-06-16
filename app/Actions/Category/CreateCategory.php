<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\User;
use App\Models\Category;

class CreateCategory
{
    public function execute(array $categoryData, User $user): Category
    {
        /** @var Category $category */
        $category = $user->categories()->create($categoryData);

        return $category;
    }
}
