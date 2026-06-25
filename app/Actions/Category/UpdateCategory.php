<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Category;

class UpdateCategory
{
    public function execute(Category $category, array $data): Category
    {
        $category->update($data);

        return $category;
    }
}
