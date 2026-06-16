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
            $table->foreignId('recurring_task_id')->nullable()->after('category_id')->constrained();

            $table->dropColumn('is_recurring');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function(Blueprint $table): void {
            $table->dropForeign('tasks_recurring_task_id_foreign');
            $table->dropColumn('recurring_task_id');

            $table->boolean('is_recurring')->default(false)->after('category_id');
        });
    }
};
