<?php

namespace App\Observers;

use App\Models\TaskChecklistItem;

class TaskChecklistItemObserver
{
    public function saving(TaskChecklistItem $item)
    {
        $company = company();

        if ($company && !$company->is_global) {
            $item->company_id = $company->id;
        }
    }
}
