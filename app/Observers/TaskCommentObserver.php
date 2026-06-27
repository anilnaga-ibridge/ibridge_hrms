<?php

namespace App\Observers;

use App\Models\TaskComment;

class TaskCommentObserver
{
    public function saving(TaskComment $comment)
    {
        $company = company();

        if ($company && !$company->is_global) {
            $comment->company_id = $company->id;
        }
    }
}
