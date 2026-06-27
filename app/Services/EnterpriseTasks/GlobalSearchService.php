<?php

namespace App\Services\EnterpriseTasks;

use App\Models\EnterpriseTasks\Task;
use App\Models\Project;
use App\Models\EnterpriseTasks\Label;
use App\Models\EnterpriseTasks\SavedFilter;
use App\Models\EnterpriseTasks\TaskTemplate;

class GlobalSearchService
{
    /**
     * Search across projects, tasks, labels, saved views, and templates.
     *
     * @param string $query
     * @param int $companyId
     * @param int $userId
     * @return array
     */
    public function search(string $query, int $companyId, int $userId): array
    {
        if (trim($query) === '') {
            return [
                'tasks' => [],
                'projects' => [],
                'labels' => [],
                'views' => [],
                'templates' => []
            ];
        }

        $q = '%' . $query . '%';

        // 1. Search Tasks
        $tasks = Task::where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where(function($sub) use ($q) {
                $sub->where('title', 'like', $q)
                   ->orWhere('description', 'like', $q)
                   ->orWhere('task_number', 'like', $q);
            })
            ->limit(10)
            ->get();

        // 2. Search Projects
        $projects = Project::where('company_id', $companyId)
            ->where(function($sub) use ($q) {
                $sub->where('name', 'like', $q)
                   ->orWhere('description', 'like', $q);
            })
            ->limit(10)
            ->get();

        // 3. Search Labels
        $labels = Label::where('company_id', $companyId)
            ->where('name', 'like', $q)
            ->limit(10)
            ->get();

        // 4. Search Saved Filters (views)
        $views = SavedFilter::where('user_id', $userId)
            ->where('name', 'like', $q)
            ->limit(10)
            ->get();

        // 5. Search Templates
        $templates = TaskTemplate::where('company_id', $companyId)
            ->where(function($sub) use ($q) {
                $sub->where('name', 'like', $q)
                   ->orWhere('description', 'like', $q);
            })
            ->limit(10)
            ->get();

        return [
            'tasks' => $tasks,
            'projects' => $projects,
            'labels' => $labels,
            'views' => $views,
            'templates' => $templates
        ];
    }
}
