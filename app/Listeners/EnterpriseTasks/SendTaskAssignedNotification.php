<?php

namespace App\Listeners\EnterpriseTasks;

use App\Events\EnterpriseTasks\TaskAssigned;
use App\Models\EnterpriseTasks\NotificationPreference;
use App\Services\EnterpriseTasks\NotificationService;
use App\Services\EnterpriseTasks\ObservabilityService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTaskAssignedNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(TaskAssigned $event): void
    {
        try {
            $task = $event->task;
            $assignee = $event->assignee;
            $companyId = $task->company_id;

            // Check notification preferences
            $prefs = NotificationPreference::where('company_id', $companyId)
                ->where('user_id', $assignee->id)
                ->first();

            // Default: email on assign is true
            $emailEnabled = $prefs ? (bool) $prefs->email_on_assign : true;
            $pushEnabled  = $prefs ? (bool) $prefs->push_on_assign : false;

            $notificationService = app(NotificationService::class);

            // In-App notification (always)
            $notificationService->createNotification($companyId, [
                'user_id'     => $assignee->id,
                'type'        => 'task_assigned',
                'title'       => 'New Task Assigned',
                'body'        => "You have been assigned to: {$task->title}",
                'link'        => "/admin/enterprise-tasks/list",
                'task_id'     => $task->id,
            ]);

            // Email notification
            if ($emailEnabled) {
                \Mail::send([], [], function ($message) use ($assignee, $task) {
                    $message->to($assignee->email, $assignee->name)
                        ->subject("Task Assigned: {$task->title}")
                        ->html(
                            "<h2>You have a new task assignment</h2>
                             <p><strong>Task:</strong> {$task->title}</p>
                             <p><strong>Priority:</strong> {$task->priority}</p>
                             <p><strong>Due:</strong> " . ($task->due_date ?? 'No deadline') . "</p>
                             <p>Please log in to view and manage your task.</p>"
                        );
                });
            }
        } catch (\Throwable $e) {
            ObservabilityService::exception($e, ObservabilityService::TYPE_NOTIFICATION_FAILURE, [
                'event' => 'task_assigned',
            ]);
        }
    }
}
