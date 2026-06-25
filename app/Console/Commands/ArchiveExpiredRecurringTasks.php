<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RecurringTask;
use Illuminate\Console\Command;

class ArchiveExpiredRecurringTasks extends Command
{
    protected $signature = 'app:archive-expired-recurring-tasks';

    protected $description = 'Archive recurring tasks that have passed their end date';

    public function handle(): void
    {
        $expired = RecurringTask::query()->whereNotNull('end_date')->where('end_date', '<', today())->delete();

        if ($expired > 0) {
            $this->info('Archived ' . $expired . ' expired recurring tasks.');
        } else {
            $this->info('There are no expired recurring tasks to archive.');
        }
    }
}
