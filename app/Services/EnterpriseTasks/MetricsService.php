<?php

namespace App\Services\EnterpriseTasks;

use App\Models\EnterpriseTasks\Task;
use App\Models\Project;
use App\Models\EnterpriseTasks\TimeLog;
use App\Models\EnterpriseTasks\TaskUser;
use App\Models\EnterpriseTasks\ProjectMetric;
use App\Models\EnterpriseTasks\TaskMetric;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class MetricsService
{
    public function calculateProjectMetrics(int $projectId): ProjectMetric
    {
        $project = Project::findOrFail($projectId);
        $tasks = Task::where('project_id', $projectId)->where('is_deleted', false)->get();

        $total = $tasks->count();
        $completed = $tasks->where('status', 'completed')->count();
        $completionPercentage = $total > 0 ? round(($completed / $total) * 100, 2) : 0.0;

        $allocatedHours = $tasks->sum('estimated_hours');
        $actualHours = $tasks->sum('actual_hours');
        $remainingHours = max(0, $allocatedHours - $actualHours);

        // Burn rate = actual hours / estimated hours (for completed tasks)
        $completedEstimates = $tasks->where('status', 'completed')->sum('estimated_hours');
        $completedActuals = $tasks->where('status', 'completed')->sum('actual_hours');
        $burnRate = $completedEstimates > 0 ? round(($completedActuals / $completedEstimates) * 100, 2) : 100.0;

        $metric = ProjectMetric::updateOrCreate(
            [
                'company_id' => $project->company_id,
                'project_id' => $projectId,
                'date' => Carbon::today()->toDateString()
            ],
            [
                'completion_percentage' => $completionPercentage,
                'burn_rate' => $burnRate,
                'allocated_hours' => $allocatedHours,
                'remaining_hours' => $remainingHours
            ]
        );

        // Invalidate Project Metrics Cache
        Cache::forget("project:metrics:{$projectId}");

        return $metric;
    }

    public function calculateTaskMetrics(int $companyId, ?int $projectId = null): TaskMetric
    {
        $query = Task::where('company_id', $companyId)->where('is_deleted', false);
        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $tasks = $query->get();
        $total = $tasks->count();
        $completed = $tasks->where('status', 'completed')->count();
        $pending = $total - $completed;

        $today = Carbon::today();
        $overdue = $tasks->filter(function($t) use ($today) {
            return $t->due_date && Carbon::parse($t->due_date)->lt($today) && $t->status !== 'completed';
        })->count();

        // Calculate time spent from time logs
        $taskIds = $tasks->pluck('id')->toArray();
        $timeSpent = 0;
        if (!empty($taskIds)) {
            $timeSpent = (int)TimeLog::whereIn('task_id', $taskIds)->sum('duration_minutes');
        }

        $metric = TaskMetric::updateOrCreate(
            [
                'company_id' => $companyId,
                'project_id' => $projectId,
                'date' => Carbon::today()->toDateString()
            ],
            [
                'total_tasks' => $total,
                'completed_tasks' => $completed,
                'pending_tasks' => $pending,
                'overdue_tasks' => $overdue,
                'time_spent_minutes' => $timeSpent
            ]
        );

        return $metric;
    }

    public function calculateDepartmentMetrics(int $companyId, int $departmentId): array
    {
        $userIds = User::where('company_id', $companyId)
            ->where('department_id', $departmentId)
            ->pluck('id')
            ->toArray();

        if (empty($userIds)) {
            return [
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'pending_tasks' => 0,
                'overdue_tasks' => 0,
                'productivity_pct' => 100
            ];
        }

        $taskIds = TaskUser::whereIn('user_id', $userIds)->pluck('task_id')->toArray();
        $tasks = Task::whereIn('id', $taskIds)
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->get();

        $total = $tasks->count();
        $completed = $tasks->where('status', 'completed')->count();
        $pending = $total - $completed;
        
        $today = Carbon::today();
        $overdue = $tasks->filter(function($t) use ($today) {
            return $t->due_date && Carbon::parse($t->due_date)->lt($today) && $t->status !== 'completed';
        })->count();

        $productivity = $total > 0 ? round(($completed / $total) * 100, 2) : 100.0;

        return [
            'total_tasks' => $total,
            'completed_tasks' => $completed,
            'pending_tasks' => $pending,
            'overdue_tasks' => $overdue,
            'productivity_pct' => $productivity
        ];
    }

    public function getCompanyDashboardCached(int $companyId)
    {
        return Cache::remember("dashboard:company:{$companyId}", 300, function() use ($companyId) {
            $projectsCount = Project::where('company_id', $companyId)->count();
            $tasks = Task::where('company_id', $companyId)->where('is_deleted', false)->where('is_archived', false)->get();
            $completed = $tasks->where('status', 'completed')->count();
            $total = $tasks->count();

            $today = Carbon::today();
            $overdue = $tasks->filter(function($t) use ($today) {
                return $t->due_date && Carbon::parse($t->due_date)->lt($today) && $t->status !== 'completed';
            })->count();

            $totalHours = $tasks->sum('estimated_hours');
            $actualHours = $tasks->sum('actual_hours');

            // Department breakdown
            $depts = User::where('company_id', $companyId)
                ->whereNotNull('department_id')
                ->groupBy('department_id')
                ->pluck('department_id');

            $deptMetrics = [];
            foreach ($depts as $dId) {
                $dName = \App\Models\Department::find($dId)->name ?? 'Dept';
                $deptMetrics[] = array_merge([
                    'name' => $dName
                ], $this->calculateDepartmentMetrics($companyId, $dId));
            }

            return [
                'total_projects' => $projectsCount,
                'total_tasks' => $total,
                'completed_tasks' => $completed,
                'overdue_tasks' => $overdue,
                'burn_rate' => $totalHours > 0 ? round(($actualHours / $totalHours) * 100, 2) : 100,
                'productivity' => $total > 0 ? round(($completed / $total) * 100, 2) : 100,
                'department_metrics' => $deptMetrics
            ];
        });
    }

    public function getUserDashboardCached(int $userId, int $companyId)
    {
        return Cache::remember("dashboard:user:{$userId}", 300, function() use ($userId, $companyId) {
            $taskIds = TaskUser::where('user_id', $userId)->pluck('task_id');
            $tasks = Task::whereIn('id', $taskIds)
                ->where('company_id', $companyId)
                ->where('is_deleted', false)
                ->where('is_archived', false)
                ->get();

            $today = Carbon::today();
            $overdueTasks = $tasks->filter(function($t) use ($today) {
                return $t->due_date && Carbon::parse($t->due_date)->lt($today) && $t->status !== 'completed';
            })->count();

            $todayTasks = $tasks->filter(function($t) use ($today) {
                return $t->due_date && Carbon::parse($t->due_date)->equalTo($today);
            })->count();

            $upcomingTasks = $tasks->filter(function($t) use ($today) {
                return $t->due_date && Carbon::parse($t->due_date)->gt($today) && $t->status !== 'completed';
            })->count();

            return [
                'my_tasks' => $tasks->count(),
                'completed_tasks' => $tasks->where('status', 'completed')->count(),
                'today_tasks' => $todayTasks,
                'overdue_tasks' => $overdueTasks,
                'upcoming_tasks' => $upcomingTasks
            ];
        });
    }

    public function getProjectMetricsCached(int $projectId): array
    {
        return Cache::remember("project:metrics:{$projectId}", 900, function() use ($projectId) {
            $m = $this->calculateProjectMetrics($projectId);
            return [
                'completion_percentage' => $m->completion_percentage,
                'burn_rate' => $m->burn_rate,
                'allocated_hours' => $m->allocated_hours,
                'remaining_hours' => $m->remaining_hours
            ];
        });
    }

    public function getDepartmentMetricsCached(int $companyId, int $departmentId): array
    {
        return Cache::remember("department:metrics:{$departmentId}", 900, function() use ($companyId, $departmentId) {
            return $this->calculateDepartmentMetrics($companyId, $departmentId);
        });
    }

    public function invalidateCache(int $companyId, ?int $userId = null, ?int $projectId = null, ?int $departmentId = null): void
    {
        Cache::forget("dashboard:company:{$companyId}");
        if ($userId) {
            Cache::forget("dashboard:user:{$userId}");
        }
        if ($projectId) {
            Cache::forget("project:metrics:{$projectId}");
        }
        if ($departmentId) {
            Cache::forget("department:metrics:{$departmentId}");
        }
    }
}
