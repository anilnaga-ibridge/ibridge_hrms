<?php

namespace App\Services\EnterpriseTasks;

use App\Repositories\EnterpriseTasks\AutomationRepository;
use App\Models\EnterpriseTasks\Task;
use App\Models\Project;
use App\Models\EnterpriseTasks\TaskUser;
use App\Models\EnterpriseTasks\TaskComment;
use App\Models\EnterpriseTasks\Dependency;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AutomationService
{
    protected AutomationRepository $repository;

    public function __construct(AutomationRepository $repository)
    {
        $this->repository = $repository;
    }

    public function triggerRules(string $eventName, Task $task): void
    {
        $rules = $this->repository->getActiveRulesForEvent($eventName, $task->project_id);

        foreach ($rules as $rule) {
            try {
                if ($this->checkConditions($rule->conditions, $task)) {
                    $this->executeActions($rule->actions, $task);
                }
            } catch (\Exception $e) {
                Log::error("Failed executing automation rule [{$rule->id}]: " . $e->getMessage());
            }
        }
    }

    private function checkConditions(?array $conditions, Task $task): bool
    {
        if (!$conditions) return true;

        // Conditions format: [['field' => 'priority', 'operator' => 'equals', 'value' => 'P1']]
        foreach ($conditions as $cond) {
            $field = $cond['field'];
            $operator = $cond['operator'];
            $value = $cond['value'];

            $currentVal = $task->{$field};

            switch ($operator) {
                case 'equals':
                    if ($currentVal != $value) return false;
                    break;
                case 'not_equals':
                    if ($currentVal == $value) return false;
                    break;
                case 'contains':
                    if (strpos($currentVal, $value) === false) return false;
                    break;
            }
        }

        return true;
    }

    private function executeActions(?array $actions, Task $task): void
    {
        if (!$actions) return;

        // Actions format: [['action' => 'change_status', 'value' => 'completed']]
        foreach ($actions as $act) {
            $action = $act['action'];
            $value = $act['value'];

            switch ($action) {
                case 'change_status':
                    $task->update(['status' => $value]);
                    break;
                case 'assign_user':
                    $userId = \App\Classes\Common::getIdFromHash($value);
                    if ($userId) {
                        TaskUser::updateOrCreate([
                            'task_id' => $task->id,
                            'user_id' => $userId,
                            'type' => 'assignee'
                        ]);
                    }
                    break;
                case 'create_comment':
                    TaskComment::create([
                        'task_id' => $task->id,
                        'user_id' => $task->created_by ?? User::first()->id,
                        'comment' => $value
                    ]);
                    break;
                case 'archive_project':
                    if ($task->project_id) {
                        Project::where('id', $task->project_id)->update(['status' => 'archived']);
                    }
                    break;
                case 'start_next_task':
                    // Move dependent tasks to 'in_progress' or 'pending'
                    $dependents = Dependency::where('depends_on_task_id', $task->id)->get();
                    foreach ($dependents as $dep) {
                        $nextTask = Task::find($dep->task_id);
                        if ($nextTask && $nextTask->status === 'pending') {
                            $nextTask->update(['status' => 'in_progress']);
                        }
                    }
                    break;
            }
        }
    }
}
