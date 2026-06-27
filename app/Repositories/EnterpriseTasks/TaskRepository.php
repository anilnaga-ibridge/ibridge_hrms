<?php

namespace App\Repositories\EnterpriseTasks;

use App\Models\EnterpriseTasks\Task;
use App\Models\EnterpriseTasks\TaskUser;
use App\Models\EnterpriseTasks\TaskLabel;
use App\Classes\Common;

class TaskRepository
{
    public function getById(int $id): ?Task
    {
        return Task::with([
            'project', 'section', 'subtasks', 'createdByUser', 'updatedByUser', 
            'checklists.items', 'comments.replies', 'attachments', 'activities.user', 'timeLogs'
        ])->find($id);
    }

    public function getWithFilters(int $companyId, array $filters, int $perPage = 50)
    {
        $query = Task::where('company_id', $companyId)
            ->where('is_deleted', false)
            ->with(['project', 'section', 'createdByUser']);

        if (isset($filters['x_project_id']) && $filters['x_project_id'] != '') {
            $query->where('project_id', Common::getIdFromHash($filters['x_project_id']));
        }

        if (isset($filters['x_section_id']) && $filters['x_section_id'] != '') {
            $query->where('section_id', Common::getIdFromHash($filters['x_section_id']));
        }

        if (isset($filters['status']) && $filters['status'] != '') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority']) && $filters['priority'] != '') {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['search']) && $filters['search'] != '') {
            $s = $filters['search'];
            $query->where(function($q) use ($s) {
                $q->where('title', 'like', "%$s%")
                  ->orWhere('description', 'like', "%$s%")
                  ->orWhere('task_number', 'like', "%$s%");
            });
        }

        if (isset($filters['parent_only']) && $filters['parent_only'] === 'true') {
            $query->whereNull('parent_id');
        }

        if (isset($filters['x_assignee_id']) && $filters['x_assignee_id'] != '') {
            $assId = Common::getIdFromHash($filters['x_assignee_id']);
            $query->whereHas('taskUsers', function($q) use ($assId) {
                $q->where('user_id', $assId)->where('type', 'assignee');
            });
        }

        if (isset($filters['label_ids']) && is_array($filters['label_ids'])) {
            $labelIds = array_map([Common::class, 'getIdFromHash'], $filters['label_ids']);
            $query->whereHas('taskLabels', function($q) use ($labelIds) {
                $q->whereIn('label_id', $labelIds);
            });
        }

        $orderBy = $filters['order_by'] ?? 'id';
        $orderDir = $filters['order_dir'] ?? 'desc';
        
        return $query->orderBy($orderBy, $orderDir)->paginate($perPage);
    }

    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function update(Task $task, array $data): bool
    {
        return $task->update($data);
    }

    public function delete(Task $task): bool
    {
        return $task->update(['is_deleted' => true]);
    }

    public function syncUsers(int $taskId, array $userIds, string $type, int $assignedBy = null): void
    {
        TaskUser::where('task_id', $taskId)->where('type', $type)->delete();
        foreach ($userIds as $uid) {
            TaskUser::create([
                'task_id' => $taskId,
                'user_id' => $uid,
                'type' => $type,
                'assigned_by' => $assignedBy,
                'assigned_date' => now()
            ]);
        }
    }

    public function syncLabels(int $taskId, array $labelIds): void
    {
        TaskLabel::where('task_id', $taskId)->delete();
        foreach ($labelIds as $lid) {
            TaskLabel::create([
                'task_id' => $taskId,
                'label_id' => $lid
            ]);
        }
    }
}
