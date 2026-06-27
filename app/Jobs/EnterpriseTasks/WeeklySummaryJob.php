<?php

namespace App\Jobs\EnterpriseTasks;

use App\Models\User;
use App\Models\EnterpriseTasks\Task;
use App\Models\EnterpriseTasks\TaskUser;
use App\Services\EnterpriseTasks\NotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WeeklySummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notificationService): void
    {
        $users = User::where('status', 'active')->get();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        foreach ($users as $user) {
            $taskIds = TaskUser::where('user_id', $user->id)->where('type', 'assignee')->pluck('task_id');
            
            $tasks = Task::whereIn('id', $taskIds)
                ->where('company_id', $user->company_id)
                ->where('is_deleted', false)
                ->get();

            $completedThisWeek = $tasks->filter(fn($t) => $t->status === 'completed' && $t->completion_date && Carbon::parse($t->completion_date)->between($startOfWeek, $endOfWeek))->count();
            $pendingTotal = $tasks->where('status', '!=', 'completed')->count();

            $data = [
                'task_title' => 'Weekly Tasks Summary',
                'message' => "Weekly Tasks Summary: You completed {$completedThisWeek} tasks this week. You currently have {$pendingTotal} pending tasks."
            ];

            $notificationService->notify($user->company_id, $user->id, 'weekly_summary', $data);
        }
    }
}
