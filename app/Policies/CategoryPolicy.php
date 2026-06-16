<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Category;

class CategoryPolicy
{
    /**
     * Determine whether the user can manage the model.
     */
    public function manage(User $user, Category $category): bool
    {
        return $category->user()->is($user);
    }
}
