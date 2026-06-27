<?php

namespace App\Listeners\EnterpriseTasks;

use App\Services\EnterpriseTasks\ProductivityService;
use App\Models\EnterpriseTasks\TaskUser;
use Illuminate\Support\Facades\Cache;

class UpdateProductivity
{
    protected ProductivityService $productivityService;

    public function __construct(ProductivityService $productivityService)
    {
        $this->productivityService = $productivityService;
    }

    public function handle($event): void
    {
        $task = null;
        if (property_exists($event, 'task')) {
            $task = $event->task;
        } else if (property_exists($event, 'comment')) {
            $task = $event->comment->task;
        }

        if ($task) {
            $uids = TaskUser::where('task_id', $task->id)->pluck('user_id')->toArray();
            if ($task->created_by) {
                $uids[] = $task->created_by;
            }

            foreach (array_unique($uids) as $uid) {
                Cache::forget("productivity:month:{$uid}");
            }
        }
    }
}
