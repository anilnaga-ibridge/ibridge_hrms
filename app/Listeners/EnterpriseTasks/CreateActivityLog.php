<?php

namespace App\Listeners\EnterpriseTasks;

use App\Models\EnterpriseTasks\TaskActivity;

class CreateActivityLog
{
    public function handle($event): void
    {
        $task = null;
        $type = 'system';
        $desc = '';

        if (property_exists($event, 'task')) {
            $task = $event->task;
        }

        $class = class_basename($event);

        switch ($class) {
            case 'TaskCreated':
                $type = 'create';
                $desc = 'Task created.';
                break;
            case 'TaskUpdated':
                $type = 'update';
                $desc = 'Task updated.';
                break;
            case 'TaskStatusChanged':
                $type = 'status_change';
                $desc = "Status changed from {$event->oldStatus} to {$event->newStatus}.";
                $task = $event->task;
                break;
            case 'TaskCompleted':
                $type = 'status_change';
                $desc = 'Task completed.';
                break;
            case 'TaskDeleted':
                $type = 'delete';
                $desc = 'Task soft deleted.';
                break;
            case 'TaskAssigned':
                $type = 'assign';
                $desc = 'Assignees updated.';
                break;
            case 'TaskCommentAdded':
                $task = $event->comment->task;
                $type = 'comment';
                $desc = 'Comment added to task.';
                break;
            case 'TaskAttachmentUploaded':
                $task = $event->attachment->task;
                $type = 'attachment';
                $desc = 'Attachment uploaded: ' . $event->attachment->file_name;
                break;
            default:
                return;
        }

        if ($task) {
            TaskActivity::create([
                'task_id' => $task->id,
                'user_id' => auth('api')->id() ?? $task->created_by,
                'activity_type' => $type,
                'description' => $desc
            ]);
        }
    }
}
