<?php

namespace App\Jobs\EnterpriseTasks;

use App\Models\EnterpriseTasks\Task;
use App\Services\EnterpriseTasks\NotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckOverdueTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notificationService): void
    {
        $today = Carbon::today();
        $overdueTasks = Task::where('is_deleted', false)
            ->where('is_archived', false)
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today)
            ->with('taskUsers')
            ->get();

        foreach ($overdueTasks as $task) {
            $assignees = $task->taskUsers->where('type', 'assignee');
            $data = [
                'task_title' => $task->title,
                'task_xid' => $task->xid,
                'message' => "Overdue Task Alert: \"{$task->title}\" is past its due date ({$task->due_date})."
            ];

            foreach ($assignees as $tu) {
                $notificationService->notify($task->company_id, $tu->user_id, 'task_overdue', $data);
            }
        }
    }
}
