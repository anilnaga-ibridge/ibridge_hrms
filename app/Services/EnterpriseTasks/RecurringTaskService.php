<?php

namespace App\Services\EnterpriseTasks;

use App\Models\EnterpriseTasks\RecurringTask;
use App\Models\EnterpriseTasks\Task;
use App\Models\EnterpriseTasks\TaskUser;
use App\Models\EnterpriseTasks\TaskLabel;
use Carbon\Carbon;
use Exception;

class RecurringTaskService
{
    /**
     * Set up recurrence for a task.
     */
    public function createRecurring(Task $task, array $data, int $userId): RecurringTask
    {
        // Deactivate any existing recurrence for this task
        RecurringTask::where('task_id', $task->id)->update(['is_active' => false]);

        $nextRun = $this->calculateNextRun(
            $data['frequency'],
            Carbon::parse($task->due_date ?? Carbon::now()),
            (int)($data['interval_value'] ?? 1),
            $data['week_days'] ?? null,
            isset($data['month_day']) ? (int)$data['month_day'] : null
        );

        return RecurringTask::create([
            'company_id' => $task->company_id,
            'task_id' => $task->id,
            'frequency' => $data['frequency'],
            'interval_value' => (int)($data['interval_value'] ?? 1),
            'week_days' => $data['week_days'] ?? null,
            'month_day' => isset($data['month_day']) ? (int)$data['month_day'] : null,
            'end_type' => $data['end_type'] ?? 'never',
            'end_date' => isset($data['end_date']) ? Carbon::parse($data['end_date'])->format('YYYY-MM-DD') : null,
            'occurrences' => isset($data['occurrences']) ? (int)$data['occurrences'] : null,
            'completed_occurrences' => 0,
            'next_run_at' => $nextRun,
            'is_active' => true,
            'created_by' => $userId
        ]);
    }

    /**
     * Trigger next run of recurring task when completed.
     */
    public function handleTaskCompletion(Task $task): ?Task
    {
        $recurring = RecurringTask::where('task_id', $task->id)->where('is_active', true)->first();
        if (!$recurring) return null;

        // Increment completions
        $recurring->increment('completed_occurrences');

        // Check end condition: occurrences limit
        if ($recurring->end_type === 'occurrences' && $recurring->completed_occurrences >= $recurring->occurrences) {
            $recurring->update(['is_active' => false]);
            return null;
        }

        // Check end condition: date limit
        $nextRun = $this->calculateNextRun(
            $recurring->frequency,
            $recurring->next_run_at ?? Carbon::now(),
            $recurring->interval_value,
            $recurring->week_days,
            $recurring->month_day
        );

        if ($recurring->end_type === 'date' && $recurring->end_date && Carbon::parse($nextRun)->isAfter(Carbon::parse($recurring->end_date))) {
            $recurring->update(['is_active' => false]);
            return null;
        }

        // Generate next task occurrence (clone current task)
        $nextTask = $this->cloneTask($task, $nextRun);

        // Update recurring task pointer to the new task
        $recurring->update([
            'task_id' => $nextTask->id,
            'next_run_at' => $nextRun
        ]);

        return $nextTask;
    }

    /**
     * Skip the current occurrence: calculate next date and move current task due date.
     */
    public function skipOccurrence(RecurringTask $recurring): void
    {
        $nextRun = $this->calculateNextRun(
            $recurring->frequency,
            $recurring->next_run_at ?? Carbon::now(),
            $recurring->interval_value,
            $recurring->week_days,
            $recurring->month_day
        );

        $task = Task::find($recurring->task_id);
        if ($task) {
            $task->update([
                'due_date' => Carbon::parse($nextRun)->format('YYYY-MM-DD'),
                'due_time' => Carbon::parse($nextRun)->format('H:i:s')
            ]);
        }

        $recurring->update([
            'next_run_at' => $nextRun
        ]);
    }

    /**
     * Pause recurrence.
     */
    public function pause(RecurringTask $recurring): void
    {
        $recurring->update(['is_active' => false]);
    }

    /**
     * Resume recurrence.
     */
    public function resume(RecurringTask $recurring): void
    {
        $recurring->update(['is_active' => true]);
    }

    /**
     * Helper to clone a task.
     */
    protected function cloneTask(Task $task, Carbon $nextRunDate): Task
    {
        // Calculate task number
        $maxNum = Task::where('company_id', $task->company_id)->count() + 1;
        $projectCode = $task->project_id ? strtoupper(substr($task->project->name, 0, 3)) : 'TASK';
        $taskNumber = $projectCode . '-' . str_pad($maxNum, 4, '0', STR_PAD_LEFT);

        $new = Task::create([
            'company_id' => $task->company_id,
            'project_id' => $task->project_id,
            'section_id' => $task->section_id,
            'parent_id' => $task->parent_id,
            'task_number' => $taskNumber,
            'title' => $task->title,
            'description' => $task->description,
            'rich_text_description' => $task->rich_text_description,
            'status' => 'pending',
            'priority' => $task->priority,
            'created_by' => $task->created_by,
            'due_date' => $nextRunDate->format('YYYY-MM-DD'),
            'due_time' => $task->due_time,
            'estimated_hours' => $task->estimated_hours,
            'actual_hours' => null,
            'completion_date' => null,
        ]);

        // Copy assignees, reviewers, watchers
        $users = TaskUser::where('task_id', $task->id)->get();
        foreach ($users as $tu) {
            TaskUser::create([
                'task_id' => $new->id,
                'user_id' => $tu->user_id,
                'type' => $tu->type,
                'assigned_by' => $tu->assigned_by,
                'assigned_date' => Carbon::now()
            ]);
        }

        // Copy labels
        $labels = TaskLabel::where('task_id', $task->id)->get();
        foreach ($labels as $tl) {
            TaskLabel::create([
                'task_id' => $new->id,
                'label_id' => $tl->label_id
            ]);
        }

        return $new;
    }

    /**
     * Calculate next recurrence Carbon datetime.
     */
    public function calculateNextRun(string $frequency, Carbon $from, int $interval = 1, ?string $weekDays = null, ?int $monthDay = null): Carbon
    {
        $next = $from->copy();

        switch ($frequency) {
            case 'daily':
                $next->addDays($interval);
                break;

            case 'weekdays':
                do {
                    $next->addDay();
                } while ($next->isWeekend());
                break;

            case 'weekly':
                if ($weekDays) {
                    $days = array_map('trim', explode(',', strtolower($weekDays)));
                    // Find next day in week matching days list
                    for ($i = 1; $i <= 7; $i++) {
                        $next->addDay();
                        $currentDayName = strtolower($next->format('l'));
                        if (in_array($currentDayName, $days)) {
                            break;
                        }
                    }
                } else {
                    $next->addWeeks($interval);
                }
                break;

            case 'monthly':
                $next->addMonths($interval);
                if ($monthDay) {
                    $next->day(min($monthDay, $next->daysInMonth));
                }
                break;

            case 'yearly':
                $next->addYears($interval);
                break;

            case 'custom':
                // Custom defaults to every X days
                $next->addDays($interval);
                break;

            default:
                $next->addDay();
                break;
        }

        return $next;
    }
}
