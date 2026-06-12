<?php

namespace App\Observers;

use App\Models\Project;

class ProjectObserver
{
    public function saving(Project $project)
    {
        $company = company();

        // Cannot put in creating, because saving is fired before creating. And we need company id for check bellow
        if ($company && !$company->is_global) {
            $project->company_id = $company->id;
        }
    }
}
