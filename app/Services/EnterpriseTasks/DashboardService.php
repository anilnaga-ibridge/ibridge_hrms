<?php

namespace App\Services\EnterpriseTasks;

use App\Services\EnterpriseTasks\MetricsService;
use App\Services\EnterpriseTasks\ProductivityService;
use App\Models\EnterpriseTasks\Task;
use App\Models\Project;
use App\Models\EnterpriseTasks\TaskUser;
use App\Models\EnterpriseTasks\TimeLog;
use App\Models\User;
use Carbon\Carbon;

class DashboardService
{
    protected MetricsService $metricsService;
    protected ProductivityService $productivityService;

    public function __construct(MetricsService $metricsService, ProductivityService $productivityService)
    {
        $this->metricsService = $metricsService;
        $this->productivityService = $productivityService;
    }

    public function getPersonalDashboard(int $companyId, int $userId): array
    {
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $cached = $this->metricsService->getUserDashboardCached($userId, $companyId);
        $prodScoreRes = $this->productivityService->getUserScoreCached($companyId, $userId, $today->year, $today->month);

        // Weekly progress: completed tasks in current week vs total in current week
        $taskIds = TaskUser::where('user_id', $userId)->pluck('task_id');
        $weeklyTasks = Task::whereIn('id', $taskIds)
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->whereBetween('due_date', [$startOfWeek, $endOfWeek])
            ->get();

        $weeklyCompleted = $weeklyTasks->where('status', 'completed')->count();
        $weeklyTotal = $weeklyTasks->count();

        // Time tracking: today's logged hours
        $todayLoggedMinutes = TimeLog::where('user_id', $userId)
            ->whereDate('start_time', $today)
            ->sum('duration_minutes');

        return array_merge($cached, [
            'productivity_score' => $prodScoreRes['final_score'],
            'weekly_progress' => [
                'completed' => $weeklyCompleted,
                'total' => $weeklyTotal,
                'percentage' => $weeklyTotal > 0 ? round(($weeklyCompleted / $weeklyTotal) * 100, 2) : 100.0
            ],
            'today_logged_hours' => round($todayLoggedMinutes / 60, 2)
        ]);
    }

    public function getManagerDashboard(int $companyId, int $userId): array
    {
        $user = User::findOrFail($userId);
        $deptId = $user->department_id;

        if (!$deptId) {
            return [
                'pending_tasks' => 0,
                'completed_tasks' => 0,
                'delayed_tasks' => 0,
                'team_workload' => [],
                'team_productivity' => 100
            ];
        }

        $teamUserIds = User::where('department_id', $deptId)
            ->where('company_id', $companyId)
            ->pluck('id')
            ->toArray();

        $taskIds = TaskUser::whereIn('user_id', $teamUserIds)->pluck('task_id')->toArray();
        $tasks = Task::whereIn('id', $taskIds)
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where('is_archived', false)
            ->get();

        $today = Carbon::today();
        $delayed = $tasks->filter(function($t) use ($today) {
            return $t->due_date && Carbon::parse($t->due_date)->lt($today) && $t->status !== 'completed';
        })->count();

        $completed = $tasks->where('status', 'completed')->count();
        $total = $tasks->count();

        // Recalculating capacity & workload
        $workload = [];
        foreach ($teamUserIds as $uId) {
            $u = User::find($uId);
            $pendingCount = TaskUser::where('user_id', $uId)
                ->where('type', 'assignee')
                ->whereHas('task', function($q) {
                    $q->where('status', '!=', 'completed')->where('is_deleted', false);
                })->count();

            // Total hours allocated vs actual logged this week
            $allocated = Task::whereIn('id', function($q) use ($uId) {
                $q->select('task_id')->from('ep_task_users')->where('user_id', $uId)->where('type', 'assignee');
            })->where('is_deleted', false)->sum('estimated_hours');

            $actual = TimeLog::where('user_id', $uId)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('duration_minutes') / 60;

            $workload[] = [
                'xid' => $u->xid,
                'name' => $u->name,
                'pending_tasks' => $pendingCount,
                'allocated_hours' => round($allocated, 2),
                'actual_hours' => round($actual, 2),
                'capacity_percentage' => $allocated > 0 ? min(100.0, round(($actual / $allocated) * 100, 2)) : 0.0
            ];
        }

        return [
            'pending_tasks' => $total - $completed,
            'completed_tasks' => $completed,
            'delayed_tasks' => $delayed,
            'team_workload' => $workload,
            'team_productivity' => $total > 0 ? round(($completed / $total) * 100, 2) : 100.0
        ];
    }

    public function getAdminDashboard(int $companyId): array
    {
        $cached = $this->metricsService->getCompanyDashboardCached($companyId);
        
        // Department productivity rankings
        $rankings = User::where('company_id', $companyId)
            ->where('status', 'active')
            ->get()
            ->map(function($u) use ($companyId) {
                $today = Carbon::today();
                $prod = $this->productivityService->getUserScoreCached($companyId, $u->id, $today->year, $today->month);
                return [
                    'xid' => $u->xid,
                    'name' => $u->name,
                    'department' => $u->department->name ?? 'N/A',
                    'score' => $prod['final_score'],
                    'rank' => $prod['rank']
                ];
            })->sortByDesc('score')->values()->take(10)->toArray();

        return array_merge($cached, [
            'rankings' => $rankings
        ]);
    }
}
