<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\User;
use App\Models\Category;
use Illuminate\Validation\ValidationException;

class ResolveCategory
{
    public function execute(?string $uuid, User $user): ?int
    {
        if (! $uuid) {
            return null;
        }

        $category = Category::where('uuid', $uuid)->first();

        if (! $category || $user->cannot('manage', $category)) {
            throw ValidationException::withMessages(['category_id' => 'The given category id does not exist.']);
        }

        return $category->id;
    }
}
