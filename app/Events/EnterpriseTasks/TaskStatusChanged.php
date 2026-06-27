<?php

namespace App\Events\EnterpriseTasks;

use App\Models\EnterpriseTasks\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskStatusChanged
{
    use Dispatchable, SerializesModels;

    public Task $task;
    public string $oldStatus;
    public string $newStatus;

    public function __construct(Task $task, string $oldStatus, string $newStatus)
    {
        $this->task = $task;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }
}
