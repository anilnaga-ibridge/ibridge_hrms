<?php

namespace App\Listeners\EnterpriseTasks;

use App\Events\EnterpriseTasks\TaskStatusChanged;
use App\Models\EnterpriseTasks\NotificationPreference;
use App\Models\EnterpriseTasks\TaskUser;
use App\Services\EnterpriseTasks\NotificationService;
use App\Services\EnterpriseTasks\ObservabilityService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOverdueNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    /**
     * Handle overdue / status change events.
     * This listener is also used by the CheckOverdueTasksJob directly.
     */
    public function handle(TaskStatusChanged $event): void
    {
        // Only notify on overdue transitions (handled by CheckOverdueTasksJob separately)
        // This handles status-based notifications
        try {
            $task = $event->task;
            $companyId = $task->company_id;
            $newStatus = $event->newStatus;

            if (!in_array($newStatus, ['completed', 'cancelled'])) {
                return;
            }

            $notificationService = app(NotificationService::class);
            $assigneeIds = TaskUser::where('task_id', $task->id)
                ->where('type', 'assignee')
                ->pluck('user_id');

            foreach ($assigneeIds as $userId) {
                $notificationService->createNotification($companyId, [
                    'user_id' => $userId,
                    'type'    => 'task_status_changed',
                    'title'   => 'Task ' . ucfirst($newStatus),
                    'body'    => "Task \"{$task->title}\" was marked as {$newStatus}",
                    'link'    => "/admin/enterprise-tasks/list",
                    'task_id' => $task->id,
                ]);
            }
        } catch (\Throwable $e) {
            ObservabilityService::exception($e, ObservabilityService::TYPE_NOTIFICATION_FAILURE, [
                'event' => 'task_status_changed',
            ]);
        }
    }

    /**
     * Called directly from CheckOverdueTasksJob to notify about overdue tasks.
     */
    public static function notifyOverdue($task): void
    {
        try {
            $companyId = $task->company_id;
            $notificationService = app(NotificationService::class);
            $assigneeIds = TaskUser::where('task_id', $task->id)
                ->where('type', 'assignee')
                ->pluck('user_id');

            foreach ($assigneeIds as $userId) {
                $prefs = NotificationPreference::where('company_id', $companyId)
                    ->where('user_id', $userId)->first();

                $emailEnabled = $prefs ? (bool) $prefs->email_on_overdue : true;

                $notificationService->createNotification($companyId, [
                    'user_id' => $userId,
                    'type'    => 'task_overdue',
                    'title'   => '⚠️ Task Overdue',
                    'body'    => "Task \"{$task->title}\" was due on {$task->due_date} and is now overdue.",
                    'link'    => "/admin/enterprise-tasks/list",
                    'task_id' => $task->id,
                ]);

                if ($emailEnabled) {
                    $recipient = \App\Models\User::find($userId);
                    if ($recipient) {
                        \Mail::send([], [], function ($msg) use ($recipient, $task) {
                            $msg->to($recipient->email, $recipient->name)
                                ->subject("⚠️ Task Overdue: {$task->title}")
                                ->html(
                                    "<h2>Task Overdue Alert</h2>
                                     <p>Your task <strong>\"{$task->title}\"</strong> was due on <strong>{$task->due_date}</strong> and has not been completed.</p>
                                     <p>Please log in to update the task status.</p>"
                                );
                        });
                    }
                }
            }
        } catch (\Throwable $e) {
            ObservabilityService::exception($e, ObservabilityService::TYPE_NOTIFICATION_FAILURE, [
                'event' => 'task_overdue',
            ]);
        }
    }
}
