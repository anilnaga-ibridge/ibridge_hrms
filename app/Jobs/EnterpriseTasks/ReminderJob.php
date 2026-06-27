<?php

namespace App\Jobs\EnterpriseTasks;

use App\Models\EnterpriseTasks\Reminder;
use App\Services\EnterpriseTasks\NotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notificationService): void
    {
        $now = Carbon::now();
        $reminders = Reminder::where('is_sent', false)
            ->where('remind_at', '<=', $now)
            ->with('task')
            ->get();

        foreach ($reminders as $reminder) {
            $task = $reminder->task;
            if ($task && $task->status !== 'completed' && !$task->is_deleted) {
                $data = [
                    'task_title' => $task->title,
                    'task_xid' => $task->xid,
                    'message' => "Task Reminder: \"{$task->title}\" is due on {$task->due_date} at {$task->due_time}."
                ];

                $notificationService->notify($task->company_id, $reminder->user_id, 'reminder', $data);
            }

            $reminder->update(['is_sent' => true]);
        }
    }
}
