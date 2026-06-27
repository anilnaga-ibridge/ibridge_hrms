<?php

namespace App\Events\EnterpriseTasks;

use App\Models\EnterpriseTasks\TaskComment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCommentAdded
{
    use Dispatchable, SerializesModels;

    public TaskComment $comment;

    public function __construct(TaskComment $comment)
    {
        $this->comment = $comment;
    }
}
