<?php

namespace App\Jobs\EnterpriseTasks;

use App\Models\EnterpriseTasks\Task;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ArchiveOldTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $cutoff = Carbon::now()->subDays(30);

        Task::where('is_deleted', false)
            ->where('is_archived', false)
            ->whereIn('status', ['completed', 'cancelled'])
            ->where('updated_at', '<=', $cutoff)
            ->update(['is_archived' => true]);
    }
}
