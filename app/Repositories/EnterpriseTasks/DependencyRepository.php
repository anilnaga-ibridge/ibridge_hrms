<?php

namespace App\Repositories\EnterpriseTasks;

use App\Models\EnterpriseTasks\Dependency;

class DependencyRepository
{
    public function getById(int $id): ?Dependency
    {
        return Dependency::find($id);
    }

    public function getForTask(int $taskId)
    {
        return Dependency::where('task_id', $taskId)->with('dependsOnTask')->get();
    }

    public function getDependents(int $taskId)
    {
        return Dependency::where('depends_on_task_id', $taskId)->with('task')->get();
    }

    public function create(array $data): Dependency
    {
        return Dependency::create($data);
    }

    public function delete(Dependency $dependency): ?bool
    {
        return $dependency->delete();
    }

    public function checkExists(int $taskId, int $dependsOnId): bool
    {
        return Dependency::where('task_id', $taskId)->where('depends_on_task_id', $dependsOnId)->exists();
    }
}
