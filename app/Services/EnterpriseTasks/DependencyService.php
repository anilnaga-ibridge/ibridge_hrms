<?php

namespace App\Services\EnterpriseTasks;

use App\Repositories\EnterpriseTasks\DependencyRepository;
use App\Models\EnterpriseTasks\Dependency;
use App\Models\EnterpriseTasks\Task;
use Exception;

class DependencyService
{
    protected DependencyRepository $repository;

    public function __construct(DependencyRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createDependency(int $companyId, int $projectId, int $taskId, int $dependsOnId, string $type, int $lagDays): Dependency
    {
        $this->validateDependency($taskId, $dependsOnId, $projectId);

        return $this->repository->create([
            'company_id' => $companyId,
            'project_id' => $projectId,
            'task_id' => $taskId,
            'depends_on_task_id' => $dependsOnId,
            'dependency_type' => $type,
            'lag_days' => $lagDays,
            'created_by' => auth('api')->id()
        ]);
    }

    public function removeDependency(int $id): bool
    {
        $dep = $this->repository->getById($id);
        if (!$dep) return false;
        return $this->repository->delete($dep);
    }

    public function validateDependency(int $taskId, int $dependsOnId, ?int $projectId = null): void
    {
        if ($taskId === $dependsOnId) {
            throw new Exception("A task cannot depend on itself (self-dependency is blocked).");
        }

        $task = Task::find($taskId);
        $dependsOn = Task::find($dependsOnId);

        if (!$task || !$dependsOn) {
            throw new Exception("Tasks not found.");
        }

        // Cross-project check
        if ($task->project_id !== $dependsOn->project_id) {
            throw new Exception("Cross-project dependencies are blocked.");
        }

        // Circular Dependency check
        if ($this->hasCircularDependency($taskId, $dependsOnId)) {
            throw new Exception("Cannot add dependency: this would create a circular dependency loop.");
        }

        if ($this->repository->checkExists($taskId, $dependsOnId)) {
            throw new Exception("Dependency relationship already exists.");
        }
    }

    public function hasCircularDependency(int $taskId, int $dependsOnId): bool
    {
        return $this->hasPath($dependsOnId, $taskId);
    }

    private function hasPath(int $startId, int $targetId, array &$visited = []): bool
    {
        if ($startId === $targetId) {
            return true;
        }

        $visited[$startId] = true;
        
        $predecessors = Dependency::where('task_id', $startId)->pluck('depends_on_task_id')->toArray();
        foreach ($predecessors as $predId) {
            if (!isset($visited[$predId])) {
                if ($this->hasPath($predId, $targetId, $visited)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isBlocked(int $taskId): bool
    {
        // Simple FS check for backward compatibility / quick checks
        $dependencies = Dependency::where('task_id', $taskId)
            ->where('dependency_type', 'finish_to_start')
            ->get();

        foreach ($dependencies as $dep) {
            $predecessor = Task::find($dep->depends_on_task_id);
            if ($predecessor && $predecessor->status !== 'completed') {
                return true;
            }
        }

        return false;
    }

    public function validateStatusTransitions(Task $task, string $newStatus): void
    {
        $dependencies = Dependency::where('task_id', $task->id)->get();

        foreach ($dependencies as $dep) {
            $predecessor = Task::find($dep->depends_on_task_id);
            if (!$predecessor) continue;

            $predStatus = $predecessor->status;

            switch ($dep->dependency_type) {
                case 'finish_to_start':
                    // Successor B cannot start (be in_progress or completed) until A is completed
                    if (in_array($newStatus, ['in_progress', 'completed']) && $predStatus !== 'completed') {
                        throw new Exception("Blocked by dependency: Predecessor task [{$predecessor->task_number}] must be completed first.");
                    }
                    break;

                case 'start_to_start':
                    // Successor B cannot start (be in_progress or completed) until A starts (in_progress or completed)
                    if (in_array($newStatus, ['in_progress', 'completed']) && !in_array($predStatus, ['in_progress', 'under_review', 'testing', 'completed'])) {
                        throw new Exception("Blocked by dependency: Predecessor task [{$predecessor->task_number}] must be started first.");
                    }
                    break;

                case 'finish_to_finish':
                    // Successor B cannot complete until A is completed
                    if ($newStatus === 'completed' && $predStatus !== 'completed') {
                        throw new Exception("Blocked by dependency: Predecessor task [{$predecessor->task_number}] must be completed before this task can be completed.");
                    }
                    break;

                case 'start_to_finish':
                    // Successor B cannot complete until A has started
                    if ($newStatus === 'completed' && !in_array($predStatus, ['in_progress', 'under_review', 'testing', 'completed'])) {
                        throw new Exception("Blocked by dependency: Predecessor task [{$predecessor->task_number}] must be started before this task can be completed.");
                    }
                    break;
            }
        }
    }

    public function calculateCriticalPath(int $projectId): array
    {
        $tasks = Task::where('project_id', $projectId)->where('is_deleted', false)->get();
        if ($tasks->isEmpty()) return [];

        $taskIds = $tasks->pluck('id')->toArray();
        
        $adj = [];
        $inDegree = [];
        foreach ($taskIds as $id) {
            $adj[$id] = [];
            $inDegree[$id] = 0;
        }

        $dependencies = Dependency::where('project_id', $projectId)->get();
        foreach ($dependencies as $dep) {
            if (in_array($dep->task_id, $taskIds) && in_array($dep->depends_on_task_id, $taskIds)) {
                $adj[$dep->depends_on_task_id][] = $dep->task_id;
                $inDegree[$dep->task_id]++;
            }
        }

        $queue = [];
        foreach ($taskIds as $id) {
            if ($inDegree[$id] === 0) {
                $queue[] = $id;
            }
        }

        $topoOrder = [];
        while (!empty($queue)) {
            $u = array_shift($queue);
            $topoOrder[] = $u;

            foreach ($adj[$u] as $v) {
                $inDegree[$v]--;
                if ($inDegree[$v] === 0) {
                    $queue[] = $v;
                }
            }
        }

        if (count($topoOrder) < count($taskIds)) {
            return [];
        }

        $es = [];
        $ef = [];
        $durations = [];
        
        foreach ($tasks as $t) {
            $durations[$t->id] = (float)($t->estimated_hours ?? 0);
            $es[$t->id] = 0.0;
            $ef[$t->id] = $durations[$t->id];
        }

        foreach ($topoOrder as $u) {
            foreach ($adj[$u] as $v) {
                if ($ef[$u] > $es[$v]) {
                    $es[$v] = $ef[$u];
                    $ef[$v] = $es[$v] + $durations[$v];
                }
            }
        }

        $projectDuration = count($ef) > 0 ? max($ef) : 0.0;

        $ls = [];
        $lf = [];
        foreach ($topoOrder as $id) {
            $lf[$id] = $projectDuration;
            $ls[$id] = $projectDuration - $durations[$id];
        }

        $revTopo = array_reverse($topoOrder);
        foreach ($revTopo as $u) {
            foreach ($adj[$u] as $v) {
                if ($ls[$v] < $lf[$u]) {
                    $lf[$u] = $ls[$v];
                    $ls[$u] = $lf[$u] - $durations[$u];
                }
            }
        }

        $criticalTaskIds = [];
        foreach ($topoOrder as $id) {
            $slack = abs($es[$id] - $ls[$id]);
            if ($slack < 0.001) {
                $criticalTaskIds[] = $id;
            }
        }

        return Task::whereIn('id', $criticalTaskIds)->pluck('id')->map(function($id) {
            return \Vinkla\Hashids\Facades\Hashids::encode($id);
        })->toArray();
    }
}
