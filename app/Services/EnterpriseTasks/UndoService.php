<?php

namespace App\Services\EnterpriseTasks;

use App\Models\EnterpriseTasks\UndoAction;
use App\Models\EnterpriseTasks\Task;
use App\Models\EnterpriseTasks\TaskUser;
use Carbon\Carbon;

class UndoService
{
    /**
     * Create an undoable action.
     */
    public function createUndo(int $userId, string $actionType, array $payload): UndoAction
    {
        // Expire in 30 seconds
        $expiresAt = Carbon::now()->addSeconds(30);

        return UndoAction::create([
            'user_id' => $userId,
            'action_type' => $actionType,
            'payload' => $payload,
            'expires_at' => $expiresAt
        ]);
    }

    /**
     * Execute an undo action and restore the previous state.
     */
    public function execute(UndoAction $action): bool
    {
        if (Carbon::parse($action->expires_at)->isPast()) {
            $action->delete();
            return false;
        }

        $payload = $action->payload;

        switch ($action->action_type) {
            case 'delete':
                if (isset($payload['task_id'])) {
                    Task::where('id', $payload['task_id'])->update(['is_deleted' => false]);
                }
                break;

            case 'complete':
            case 'status_change':
                if (isset($payload['task_id']) && isset($payload['old_status'])) {
                    Task::where('id', $payload['task_id'])->update(['status' => $payload['old_status']]);
                }
                break;

            case 'move':
                if (isset($payload['task_id'])) {
                    $task = Task::find($payload['task_id']);
                    if ($task) {
                        $task->update([
                            'project_id' => $payload['old_project_id'] ?? null,
                            'section_id' => $payload['old_section_id'] ?? null
                        ]);
                    }
                }
                break;

            case 'assignment':
                if (isset($payload['task_id']) && isset($payload['old_assignees'])) {
                    TaskUser::where('task_id', $payload['task_id'])->where('type', 'assignee')->delete();
                    foreach ($payload['old_assignees'] as $assignee) {
                        TaskUser::create([
                            'task_id' => $payload['task_id'],
                            'user_id' => $assignee['user_id'],
                            'type' => 'assignee',
                            'assigned_by' => $assignee['assigned_by'],
                            'assigned_date' => $assignee['assigned_date']
                        ]);
                    }
                }
                break;

            default:
                return false;
        }

        $action->delete();
        return true;
    }
}
