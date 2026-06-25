<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ListCategories
{
    public function execute(User $user): LengthAwarePaginator
    {
        return $user->categories()
            ->latest()
            ->paginate();
    }
}
