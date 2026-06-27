<?php

namespace App\Listeners\EnterpriseTasks;

use App\Services\EnterpriseTasks\NotificationService;
use App\Models\EnterpriseTasks\TaskUser;

class SendNotifications
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle($event): void
    {
        $class = class_basename($event);

        switch ($class) {
            case 'TaskAssigned':
                $task = $event->task;
                $data = [
                    'task_title' => $task->title,
                    'task_xid' => $task->xid,
                    'message' => "You have been assigned to task: {$task->title}"
                ];
                foreach ($event->userIds as $uid) {
                    if ($uid != auth('api')->id()) {
                        $this->notificationService->notify($task->company_id, $uid, 'task_assigned', $data);
                    }
                }
                break;

            case 'TaskCompleted':
                $task = $event->task;
                $data = [
                    'task_title' => $task->title,
                    'task_xid' => $task->xid,
                    'message' => "Task completed: {$task->title}"
                ];
                $userIds = TaskUser::where('task_id', $task->id)->whereIn('type', ['watcher', 'reviewer'])->pluck('user_id')->toArray();
                if ($task->created_by) {
                    $userIds[] = $task->created_by;
                }
                $userIds = array_unique($userIds);

                foreach ($userIds as $uid) {
                    if ($uid != auth('api')->id()) {
                        $this->notificationService->notify($task->company_id, $uid, 'task_completed', $data);
                    }
                }
                break;

            case 'TaskCommentAdded':
                $comment = $event->comment;
                $task = $comment->task;
                $data = [
                    'task_title' => $task->title,
                    'task_xid' => $task->xid,
                    'message' => "New comment added to task {$task->title}"
                ];
                $userIds = TaskUser::where('task_id', $task->id)->whereIn('type', ['assignee', 'watcher'])->pluck('user_id')->toArray();
                if ($task->created_by) {
                    $userIds[] = $task->created_by;
                }
                $userIds = array_unique($userIds);

                foreach ($userIds as $uid) {
                    if ($uid != auth('api')->id()) {
                        $this->notificationService->notify($task->company_id, $uid, 'comment_added', $data);
                    }
                }
                break;
        }
    }
}
