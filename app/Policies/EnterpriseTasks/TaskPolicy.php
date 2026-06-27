<?php

namespace App\Policies\EnterpriseTasks;

use App\Models\User;
use App\Models\EnterpriseTasks\Task;
use App\Models\EnterpriseTasks\TaskUser;
use App\Models\Project;

class TaskPolicy
{
    private function canAccessTask(User $user, Task $task): bool
    {
        // Global admin has absolute access
        if ($user->role && $user->role->name === 'admin') {
            return true;
        }

        // Creator has access
        if ($task->created_by === $user->id) {
            return true;
        }

        // Assignees / Watchers / Reviewers have access
        $isAssigned = TaskUser::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->exists();
        if ($isAssigned) {
            return true;
        }

        // If it's a project task, check project membership
        if ($task->project_id && $task->project) {
            $isProjectMember = is_array($task->project->members) && in_array($user->xid, $task->project->members);
            if ($isProjectMember) {
                return true;
            }

            if ($task->project->created_by === $user->id) {
                return true;
            }
        }

        return false;
    }

    private function getProjectRole(User $user, Task $task): ?string
    {
        if (!$task->project_id) {
            return 'owner'; // Creator of personal task has full owner rights
        }

        if ($user->role && $user->role->name === 'admin') {
            return 'admin';
        }

        if ($task->project) {
            if ($task->project->created_by === $user->id) {
                return 'owner';
            }
            if (is_array($task->project->members) && in_array($user->xid, $task->project->members)) {
                return 'member';
            }
        }

        return null;
    }

    public function view(User $user, Task $task): bool
    {
        return $this->canAccessTask($user, $task);
    }

    public function create(User $user): bool
    {
        return true; // Anyone can create a task
    }

    public function update(User $user, Task $task): bool
    {
        if (!$this->canAccessTask($user, $task)) {
            return false;
        }

        $role = $this->getProjectRole($user, $task);
        if ($role === 'read_only') {
            // Read only member can only update if they are assigned to the task
            return TaskUser::where('task_id', $task->id)
                ->where('user_id', $user->id)
                ->where('type', 'assignee')
                ->exists();
        }

        return true;
    }

    public function delete(User $user, Task $task): bool
    {
        if ($task->created_by === $user->id) {
            return true;
        }

        $role = $this->getProjectRole($user, $task);
        return in_array($role, ['owner', 'admin']);
    }

    public function assign(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    public function comment(User $user, Task $task): bool
    {
        return $this->canAccessTask($user, $task);
    }

    public function upload(User $user, Task $task): bool
    {
        return $this->canAccessTask($user, $task);
    }

    public function changeStatus(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    public function trackTime(User $user, Task $task): bool
    {
        return $this->canAccessTask($user, $task);
    }
}
