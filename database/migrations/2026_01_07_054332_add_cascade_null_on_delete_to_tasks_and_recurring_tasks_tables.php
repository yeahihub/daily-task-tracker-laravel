<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function(Blueprint $table): void {
            $table->dropForeign(['category_id']);

            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
        });

        Schema::table('recurring_tasks', function(Blueprint $table): void {
            $table->dropForeign(['category_id']);

            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
        });
    }
};
