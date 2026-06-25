<?php

declare(strict_types=1);

namespace App\Console\Commands;

use DateTime;
use Exception;
use App\Models\Task;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use App\Enums\TaskFrequency;
use App\Models\RecurringTask;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class GenerateRecurringTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-recurring-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate recurring tasks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetDate = today();

        $recurringTasksQuery = RecurringTask::query()
            ->where(fn(Builder $query) => $query->whereNull('start_date')->orWhere('start_date', '<=', $targetDate))
            ->where(fn(Builder $query) => $query->whereNull('end_date')->orWhere('end_date', '>=', $targetDate))
            ->whereDoesntHave('tasks', fn($q) => $q->whereDate('task_date', $targetDate));

        $totalRecurringTasks = $recurringTasksQuery->count();

        if (! $totalRecurringTasks) {
            $this->info('No active recurring tasks found.');

            return self::FAILURE;
        }

        $this->info('Processing ' . $totalRecurringTasks . ' recurring task templates...');

        $created = 0;
        $skipped = 0;

        $recurringTasksQuery->chunkById(
            250,
            function(Collection $recurringTasks) use ($targetDate, &$skipped, &$created): void {
                try {
                    $insertTasksBatch = [];

                    foreach ($recurringTasks as $recurringTask) {
                        try {
                            if (! $this->isRecurringTaskDue($recurringTask, $targetDate)) {
                                $skipped++;

                                continue;
                            }

                            $now = new DateTime();

                            // 5. Create the task instance
                            $insertTasksBatch[] = [
                                'uuid'              => (string) Str::uuid7(),
                                'user_id'           => $recurringTask->user_id,
                                'category_id'       => $recurringTask->category_id,
                                'title'             => $recurringTask->title,
                                'description'       => $recurringTask->description,
                                'recurring_task_id' => $recurringTask->id,
                                'task_date'         => $targetDate,
                                'created_at'        => $now,
                                'updated_at'        => $now,
                            ];
                        } catch (Exception $e) {
                            report($e);
                        }
                    }

                    if ($insertTasksBatch) {
                        Task::insert($insertTasksBatch);

                        $created += count($insertTasksBatch);
                    }
                } catch (Exception $e) {
                    report($e);
                }
            }
        );

        $this->info('Created ' . $created . ' recurring tasks.');

        if ($skipped > 0) {
            $this->warn('Skipped ' . $skipped . ' recurring tasks.');
        }

        $this->newLine();

        return self::SUCCESS;
    }

    private function isRecurringTaskDue(RecurringTask $recurringTask, CarbonInterface $targetDate): ?bool
    {
        return match ($recurringTask->frequency) {
            TaskFrequency::Daily    => true,
            TaskFrequency::Weekdays => $targetDate->isWeekday(),
            TaskFrequency::Weekly   => $this->isWeeklyRecurringTaskDue($recurringTask, $targetDate),
            TaskFrequency::Monthly  => $this->isMonthlyRecurringTaskDue($recurringTask, $targetDate),
        };
    }

    private function isWeeklyRecurringTaskDue(RecurringTask $recurringTask, CarbonInterface $targetDate): bool
    {
        $config = $recurringTask->frequency_config;

        if (! $config || ! isset($config['days']) || ! is_array($config['days'])) {
            return false;
        }

        return in_array(strtolower($targetDate->englishDayOfWeek), $config['days'], true);
    }

    private function isMonthlyRecurringTaskDue(RecurringTask $recurringTask, CarbonInterface $targetDate): bool
    {
        $config = $recurringTask->frequency_config;

        if (! $config || ! isset($config['day'])) {
            return false;
        }

        $dayOfMonth = min($config['day'], $targetDate->daysInMonth);

        return $targetDate->day === $dayOfMonth;
    }
}
