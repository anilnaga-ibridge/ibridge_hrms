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

class DailySummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notificationService): void
    {
        $users = User::where('status', 'active')->get();
        $today = Carbon::today()->toDateString();

        foreach ($users as $user) {
            $taskIds = TaskUser::where('user_id', $user->id)->where('type', 'assignee')->pluck('task_id');
            
            $tasks = Task::whereIn('id', $taskIds)
                ->where('company_id', $user->company_id)
                ->where('is_deleted', false)
                ->get();

            $completedToday = $tasks->filter(fn($t) => $t->status === 'completed' && $t->completion_date && Carbon::parse($t->completion_date)->toDateString() === $today)->count();
            $pendingTotal = $tasks->where('status', '!=', 'completed')->count();
            $overdueTotal = $tasks->filter(fn($t) => $t->status !== 'completed' && $t->due_date && Carbon::parse($t->due_date)->lt($today))->count();

            if ($completedToday > 0 || $pendingTotal > 0) {
                $data = [
                    'task_title' => 'Daily Tasks Summary',
                    'message' => "Daily Tasks Summary: You completed {$completedToday} tasks today. You have {$pendingTotal} pending tasks remaining ({$overdueTotal} overdue)."
                ];

                $notificationService->notify($user->company_id, $user->id, 'daily_summary', $data);
            }
        }
    }
}
