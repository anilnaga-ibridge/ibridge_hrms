<?php

namespace App\Listeners\EnterpriseTasks;

use App\Events\EnterpriseTasks\TaskCommentAdded;
use App\Models\EnterpriseTasks\NotificationPreference;
use App\Models\EnterpriseTasks\TaskUser;
use App\Services\EnterpriseTasks\NotificationService;
use App\Services\EnterpriseTasks\ObservabilityService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTaskCommentNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(TaskCommentAdded $event): void
    {
        try {
            $comment = $event->comment;
            $task = $comment->task;
            $commenter = $event->commenter;
            $companyId = $task->company_id;

            $notificationService = app(NotificationService::class);

            // Notify all assignees except the commenter
            $assigneeUserIds = TaskUser::where('task_id', $task->id)
                ->where('type', 'assignee')
                ->where('user_id', '!=', $commenter->id)
                ->pluck('user_id');

            foreach ($assigneeUserIds as $userId) {
                $prefs = NotificationPreference::where('company_id', $companyId)
                    ->where('user_id', $userId)
                    ->first();

                $emailEnabled = $prefs ? (bool) $prefs->email_on_comment : true;

                // In-App notification (always)
                $notificationService->createNotification($companyId, [
                    'user_id' => $userId,
                    'type'    => 'task_comment',
                    'title'   => 'New Comment',
                    'body'    => "{$commenter->name} commented on: {$task->title}",
                    'link'    => "/admin/enterprise-tasks/list",
                    'task_id' => $task->id,
                ]);

                // Email notification
                if ($emailEnabled) {
                    $recipient = \App\Models\User::find($userId);
                    if ($recipient) {
                        \Mail::send([], [], function ($message) use ($recipient, $commenter, $task, $comment) {
                            $message->to($recipient->email, $recipient->name)
                                ->subject("New comment on: {$task->title}")
                                ->html(
                                    "<h2>New comment on your task</h2>
                                     <p><strong>Task:</strong> {$task->title}</p>
                                     <p><strong>{$commenter->name} said:</strong></p>
                                     <blockquote>" . strip_tags($comment->content) . "</blockquote>"
                                );
                        });
                    }
                }
            }
        } catch (\Throwable $e) {
            ObservabilityService::exception($e, ObservabilityService::TYPE_NOTIFICATION_FAILURE, [
                'event' => 'task_comment_added',
            ]);
        }
    }
}
