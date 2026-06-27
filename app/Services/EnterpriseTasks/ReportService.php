<?php

namespace App\Services\EnterpriseTasks;

use App\Models\EnterpriseTasks\Task;
use App\Models\Project;
use App\Models\EnterpriseTasks\TimeLog;
use App\Models\EnterpriseTasks\TaskUser;
use App\Models\User;
use App\Services\EnterpriseTasks\ProductivityService;
use App\Exports\EnterpriseReportExport;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class ReportService
{
    protected ProductivityService $productivityService;

    public function __construct(ProductivityService $productivityService)
    {
        $this->productivityService = $productivityService;
    }

    public function getReportData(int $companyId, string $type, array $filters = []): array
    {
        $today = Carbon::today();
        $year = isset($filters['year']) ? (int)$filters['year'] : $today->year;
        $month = isset($filters['month']) ? (int)$filters['month'] : $today->month;

        switch ($type) {
            case 'productivity':
                $users = User::where('company_id', $companyId)->where('status', 'active')->get();
                return $users->map(function($u) use ($companyId, $year, $month) {
                    $scoreRes = $this->productivityService->getUserScoreCached($companyId, $u->id, $year, $month);
                    $score = $scoreRes['final_score'];

                    $assigned = TaskUser::where('user_id', $u->id)->where('type', 'assignee')->pluck('task_id');
                    $total = Task::whereIn('id', $assigned)->where('company_id', $companyId)->where('is_deleted', false)->count();
                    $completed = Task::whereIn('id', $assigned)->where('company_id', $companyId)->where('is_deleted', false)->where('status', 'completed')->count();

                    return [
                        'xid' => $u->xid,
                        'name' => $u->name,
                        'department' => $u->department ? $u->department->name : 'N/A',
                        'score' => $score,
                        'total_tasks' => $total,
                        'completed_tasks' => $completed
                    ];
                })->sortByDesc('score')->values()->toArray();

            case 'progress':
                $projects = Project::where('company_id', $companyId)->get();
                return $projects->map(function($p) {
                    $total = Task::where('project_id', $p->id)->where('is_deleted', false)->count();
                    $completed = Task::where('project_id', $p->id)->where('is_deleted', false)->where('status', 'completed')->count();
                    return [
                        'xid' => $p->xid,
                        'name' => $p->name,
                        'owner' => $p->owner ? $p->owner->name : 'N/A',
                        'total_tasks' => $total,
                        'completed_tasks' => $completed,
                        'progress' => $total > 0 ? round(($completed / $total) * 100) : 0
                    ];
                })->toArray();

            case 'overdue':
                $tasks = Task::where('company_id', $companyId)
                    ->where('is_deleted', false)
                    ->where('status', '!=', 'completed')
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', $today)
                    ->with(['project', 'taskUsers.user'])
                    ->get();

                return $tasks->map(function($t) {
                    $assignees = $t->taskUsers->where('type', 'assignee')->map(fn($tu) => $tu->user->name ?? '')->filter()->implode(', ');
                    return [
                        'xid' => $t->xid,
                        'task_number' => $t->task_number,
                        'title' => $t->title,
                        'project' => $t->project ? $t->project->name : 'N/A',
                        'due_date' => $t->due_date ? Carbon::parse($t->due_date)->format('d M Y') : 'N/A',
                        'assignees' => $assignees
                    ];
                })->toArray();

            case 'timelog':
                $users = User::where('company_id', $companyId)->get();
                return $users->map(function($u) {
                    $totalMinutes = TimeLog::where('user_id', $u->id)->sum('duration_minutes');
                    return [
                        'xid' => $u->xid,
                        'name' => $u->name,
                        'total_hours' => round($totalMinutes / 60, 2)
                    ];
                })->filter(function($item) {
                    return $item['total_hours'] > 0;
                })->values()->toArray();

            default:
                return [];
        }
    }

    public function exportExcel(int $companyId, string $type, array $filters = [])
    {
        $reportData = $this->getReportData($companyId, $type, $filters);
        $headings = [];
        $rows = [];

        switch ($type) {
            case 'productivity':
                $headings = ['Employee Name', 'Department', 'Productivity Score', 'Total Tasks', 'Completed Tasks'];
                $rows = array_map(fn($item) => [$item['name'], $item['department'], $item['score'], $item['total_tasks'], $item['completed_tasks']], $reportData);
                break;
            case 'progress':
                $headings = ['Project Name', 'Owner', 'Total Tasks', 'Completed Tasks', 'Progress Percentage'];
                $rows = array_map(fn($item) => [$item['name'], $item['owner'], $item['total_tasks'], $item['completed_tasks'], $item['progress'] . '%'], $reportData);
                break;
            case 'overdue':
                $headings = ['Task Number', 'Task Title', 'Project', 'Due Date', 'Assignees'];
                $rows = array_map(fn($item) => [$item['task_number'], $item['title'], $item['project'], $item['due_date'], $item['assignees']], $reportData);
                break;
            case 'timelog':
                $headings = ['Employee Name', 'Total Logged Hours'];
                $rows = array_map(fn($item) => [$item['name'], $item['total_hours']], $reportData);
                break;
        }

        return new EnterpriseReportExport($headings, $rows);
    }
}
