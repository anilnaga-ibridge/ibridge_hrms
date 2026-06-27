<?php

namespace App\Jobs\EnterpriseTasks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\EnterpriseTasks\RecurringTaskService;
use App\Models\EnterpriseTasks\RecurringTask;
use Carbon\Carbon;

class RecurringTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle()
    {
        $service = app(RecurringTaskService::class);
        
        // Find active recurring tasks whose next_run_at has arrived
        $active = RecurringTask::where('is_active', true)
            ->where('next_run_at', '<=', Carbon::now())
            ->get();

        foreach ($active as $rec) {
            $task = $rec->task;
            // Generate next occurrence only if current task has been completed
            if ($task && $task->status === 'completed') {
                $service->handleTaskCompletion($task);
            }
        }
    }
}
