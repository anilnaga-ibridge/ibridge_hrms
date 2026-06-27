<?php

namespace App\Repositories\EnterpriseTasks;

use App\Models\EnterpriseTasks\UserProductivityScore;
use App\Models\EnterpriseTasks\TaskMetric;
use App\Models\EnterpriseTasks\ProjectMetric;

class ProductivityRepository
{
    public function getProductivityScores(int $companyId, int $year, int $month)
    {
        return UserProductivityScore::where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->with('user')
            ->orderBy('final_score', 'desc')
            ->get();
    }

    public function getLatestTaskMetric(int $companyId, ?int $projectId = null)
    {
        $query = TaskMetric::where('company_id', $companyId);
        if ($projectId) {
            $query->where('project_id', $projectId);
        } else {
            $query->whereNull('project_id');
        }
        return $query->orderBy('date', 'desc')->first();
    }

    public function getLatestProjectMetric(int $projectId)
    {
        return ProjectMetric::where('project_id', $projectId)->orderBy('date', 'desc')->first();
    }

    public function saveProductivityScore(array $data): UserProductivityScore
    {
        return UserProductivityScore::updateOrCreate(
            [
                'company_id' => $data['company_id'],
                'user_id' => $data['user_id'],
                'year' => $data['year'],
                'month' => $data['month']
            ],
            $data
        );
    }

    public function saveTaskMetric(array $data): TaskMetric
    {
        return TaskMetric::create($data);
    }

    public function saveProjectMetric(array $data): ProjectMetric
    {
        return ProjectMetric::create($data);
    }
}
