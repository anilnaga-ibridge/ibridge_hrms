<?php

namespace App\Services\EnterpriseTasks;

use App\Repositories\EnterpriseTasks\TaskRepository;
use App\DTOs\EnterpriseTasks\TaskDTO;
use App\Models\EnterpriseTasks\Task;
use App\Models\Project;
use App\Models\EnterpriseTasks\Checklist;
use App\Models\EnterpriseTasks\ChecklistItem;
use App\Events\EnterpriseTasks\TaskCreated;
use App\Events\EnterpriseTasks\TaskUpdated;
use App\Events\EnterpriseTasks\TaskStatusChanged;
use App\Events\EnterpriseTasks\TaskCompleted;
use App\Events\EnterpriseTasks\TaskDeleted;
use App\Events\EnterpriseTasks\TaskAssigned;
use App\Services\EnterpriseTasks\DependencyService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TaskService
{
    protected TaskRepository $repository;
    protected DependencyService $dependencyService;

    public function __construct(TaskRepository $repository, DependencyService $dependencyService)
    {
        $this->repository = $repository;
        $this->dependencyService = $dependencyService;
    }

    public function createTask(TaskDTO $dto, int $companyId, int $userId): Task
    {
        return DB::transaction(function() use ($dto, $companyId, $userId) {
            // Auto generation of Task Number
            $projectCode = $dto->project_id ? strtoupper(substr(Project::find($dto->project_id)->name, 0, 3)) : 'TASK';
            $maxNum = Task::where('company_id', $companyId)->count() + 1;
            $taskNumber = $projectCode . '-' . str_pad($maxNum, 4, '0', STR_PAD_LEFT);

            $nextRecurrence = $this->calculateNextRecurrence($dto->recurrence_type, $dto->due_date, $dto->recurrence_pattern);

            $task = $this->repository->create(array_merge($dto->toArray(), [
                'company_id' => $companyId,
                'task_number' => $taskNumber,
                'created_by' => $userId,
                'updated_by' => $userId,
                'next_recurrence_date' => $nextRecurrence
            ]));

            // Sync users
            $this->repository->syncUsers($task->id, $dto->assignees_ids, 'assignee', $userId);
            $this->repository->syncUsers($task->id, $dto->reviewers_ids, 'reviewer', $userId);
            $this->repository->syncUsers($task->id, $dto->watchers_ids, 'watcher', $userId);
            
            // Sync labels
            $this->repository->syncLabels($task->id, $dto->labels_ids);

            event(new TaskCreated($task));

            if (!empty($dto->assignees_ids)) {
                event(new TaskAssigned($task, $dto->assignees_ids));
            }

            return $task;
        });
    }

    public function updateTask(Task $task, TaskDTO $dto, int $userId): Task
    {
        return DB::transaction(function() use ($task, $dto, $userId) {
            $oldStatus = $task->status;
            $oldPriority = $task->priority;
            $oldAssigneeIds = $task->taskUsers()->where('type', 'assignee')->pluck('user_id')->toArray();

            // Validate status transitions using DependencyService
            if ($oldStatus !== $dto->status) {
                $this->dependencyService->validateStatusTransitions($task, $dto->status);
            }

            $updateData = $dto->toArray();
            if ($dto->status === 'completed' && $oldStatus !== 'completed') {
                $updateData['completion_date'] = Carbon::now();
            }

            $updateData['updated_by'] = $userId;

            $this->repository->update($task, $updateData);

            // Sync relations
            $this->repository->syncUsers($task->id, $dto->assignees_ids, 'assignee', $userId);
            $this->repository->syncUsers($task->id, $dto->reviewers_ids, 'reviewer', $userId);
            $this->repository->syncUsers($task->id, $dto->watchers_ids, 'watcher', $userId);
            $this->repository->syncLabels($task->id, $dto->labels_ids);

            // Reload relationships
            $task->refresh();

            // Dispatch events
            event(new TaskUpdated($task));

            if ($oldStatus !== $task->status) {
                event(new TaskStatusChanged($task, $oldStatus, $task->status));
                if ($task->status === 'completed') {
                    event(new TaskCompleted($task));

                    // Recurrence generation
                    if ($task->recurrence_type !== 'none') {
                        $this->generateNextRecurringTask($task);
                    }
                }
            }

            $newAssigneeIds = array_diff($dto->assignees_ids, $oldAssigneeIds);
            if (!empty($newAssigneeIds)) {
                event(new TaskAssigned($task, array_values($newAssigneeIds)));
            }

            return $task;
        });
    }

    public function deleteTask(Task $task): bool
    {
        $deleted = $this->repository->delete($task);
        if ($deleted) {
            event(new TaskDeleted($task));
        }
        return $deleted;
    }

    public function toggleComplete(Task $task, int $userId): Task
    {
        $newStatus = $task->status === 'completed' ? 'pending' : 'completed';
        
        $dtoData = [
            'title' => $task->title,
            'description' => $task->description,
            'rich_text_description' => $task->rich_text_description,
            'status' => $newStatus,
            'priority' => $task->priority,
            'due_date' => $task->due_date,
            'due_time' => $task->due_time,
            'start_date' => $task->start_date,
            'start_time' => $task->start_time,
            'estimated_hours' => $task->estimated_hours,
            'actual_hours' => $task->actual_hours,
            'recurrence_type' => $task->recurrence_type,
            'recurrence_pattern' => $task->recurrence_pattern,
            'assignees_xids' => $task->taskUsers()->where('type', 'assignee')->get()->map(fn($tu) => \Vinkla\Hashids\Facades\Hashids::encode($tu->user_id))->toArray(),
            'reviewers_xids' => $task->taskUsers()->where('type', 'reviewer')->get()->map(fn($tu) => \Vinkla\Hashids\Facades\Hashids::encode($tu->user_id))->toArray(),
            'watchers_xids' => $task->taskUsers()->where('type', 'watcher')->get()->map(fn($tu) => \Vinkla\Hashids\Facades\Hashids::encode($tu->user_id))->toArray(),
            'labels_xids' => $task->taskLabels()->get()->map(fn($tl) => \Vinkla\Hashids\Facades\Hashids::encode($tl->label_id))->toArray(),
        ];

        if ($task->project_id) {
            $dtoData['x_project_id'] = \Vinkla\Hashids\Facades\Hashids::encode($task->project_id);
        }
        if ($task->section_id) {
            $dtoData['x_section_id'] = \Vinkla\Hashids\Facades\Hashids::encode($task->section_id);
        }
        if ($task->parent_id) {
            $dtoData['x_parent_id'] = \Vinkla\Hashids\Facades\Hashids::encode($task->parent_id);
        }

        $dto = new TaskDTO($dtoData);
        return $this->updateTask($task, $dto, $userId);
    }

    public function generateNextRecurringTask(Task $task): void
    {
        $nextDate = $this->calculateNextRecurrence($task->recurrence_type, $task->due_date, $task->recurrence_pattern);
        if (!$nextDate) return;

        // Generate the new task
        $maxNum = Task::where('company_id', $task->company_id)->count() + 1;
        $projectCode = $task->project_id ? strtoupper(substr(Project::find($task->project_id)->name, 0, 3)) : 'TASK';
        $taskNumber = $projectCode . '-' . str_pad($maxNum, 4, '0', STR_PAD_LEFT);

        $newTask = Task::create([
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
            'updated_by' => $task->created_by,
            'due_date' => $nextDate,
            'due_time' => $task->due_time,
            'start_date' => $task->start_date ? Carbon::parse($nextDate)->subDays(Carbon::parse($task->start_date)->diffInDays(Carbon::parse($task->due_date)))->toDateString() : null,
            'start_time' => $task->start_time,
            'estimated_hours' => $task->estimated_hours,
            'recurrence_type' => $task->recurrence_type,
            'recurrence_pattern' => $task->recurrence_pattern,
            'next_recurrence_date' => $this->calculateNextRecurrence($task->recurrence_type, $nextDate, $task->recurrence_pattern)
        ]);

        // Copy users and labels
        foreach ($task->taskUsers as $tu) {
            $newTask->taskUsers()->create([
                'user_id' => $tu->user_id,
                'type' => $tu->type,
                'assigned_by' => $tu->assigned_by,
                'assigned_date' => Carbon::now()
            ]);
        }
        foreach ($task->taskLabels as $tl) {
            $newTask->taskLabels()->create([
                'label_id' => $tl->label_id
            ]);
        }

        // Update the original task recurrence next date to null/or update next recurrence date
        $task->update(['next_recurrence_date' => null, 'recurrence_type' => 'none']);

        event(new TaskCreated($newTask));
    }

    public function calculateNextRecurrence(string $type, ?string $currentDueDate, ?string $pattern = null): ?string
    {
        if ($type === 'none' || !$currentDueDate) {
            return null;
        }

        $date = Carbon::parse($currentDueDate);

        switch ($type) {
            case 'daily':
                return $date->addDay()->toDateString();
            case 'weekly':
                return $date->addWeek()->toDateString();
            case 'monthly':
                return $date->addMonth()->toDateString();
            case 'yearly':
                return $date->addYear()->toDateString();
            case 'custom':
                if ($pattern && is_numeric($pattern)) {
                    return $date->addDays((int)$pattern)->toDateString();
                }
                return $date->addWeek()->toDateString(); // Default fallback
            default:
                return null;
        }
    }
}
