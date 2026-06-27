<?php

namespace App\Services\EnterpriseTasks;

use App\Models\Project;
use App\Models\EnterpriseTasks\ProjectSection;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    public function ensureInboxProjectExists(int $companyId, int $userId): Project
    {
        $inbox = Project::where('company_id', $companyId)
            ->where('created_by', $userId)
            ->where('is_inbox', true)
            ->first();

        if ($inbox) {
            return $inbox;
        }

        return DB::transaction(function() use ($companyId, $userId) {
            $inbox = new Project();
            $inbox->company_id = $companyId;
            $inbox->name = 'Inbox';
            $inbox->description = 'Your default task inbox.';
            $inbox->status = 'active';
            $inbox->created_by = $userId;
            $inbox->start_date = now()->toDateString();
            $inbox->is_system = true;
            $inbox->is_inbox = true;
            $inbox->members = [\Vinkla\Hashids\Facades\Hashids::encode($userId)];
            $inbox->save();

            // Default sections
            $sections = ['Inbox Tasks'];
            foreach ($sections as $index => $name) {
                ProjectSection::create([
                    'project_id' => $inbox->id,
                    'name' => $name,
                    'sort_order' => $index
                ]);
            }

            return $inbox;
        });
    }
}
