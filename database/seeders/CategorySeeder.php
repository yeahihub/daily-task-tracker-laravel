<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            Category::factory()
                ->count(rand(25, 50))
                ->for($user)
                ->create();
        }
    }
}
