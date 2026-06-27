<?php

namespace App\Events\EnterpriseTasks;

use App\Models\EnterpriseTasks\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskAssigned
{
    use Dispatchable, SerializesModels;

    public Task $task;
    public array $userIds;

    public function __construct(Task $task, array $userIds)
    {
        $this->task = $task;
        $this->userIds = $userIds;
    }
}
