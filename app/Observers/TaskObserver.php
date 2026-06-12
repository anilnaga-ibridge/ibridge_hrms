<?php

namespace App\Observers;

use App\Models\Task;

class TaskObserver
{
    public function saving(Task $task)
    {
        $company = company();

        // Cannot put in creating, because saving is fired before creating. And we need company id for check bellow
        if ($company && !$company->is_global) {
            $task->company_id = $company->id;
        }
    }
}
