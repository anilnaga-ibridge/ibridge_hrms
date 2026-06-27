<?php

namespace App\Listeners\EnterpriseTasks;

use App\Services\EnterpriseTasks\MetricsService;

class UpdateMetrics
{
    protected MetricsService $metricsService;

    public function __construct(MetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    public function handle($event): void
    {
        $task = null;
        if (property_exists($event, 'task')) {
            $task = $event->task;
        } else if (property_exists($event, 'comment')) {
            $task = $event->comment->task;
        } else if (property_exists($event, 'attachment')) {
            $task = $event->attachment->task;
        }

        if ($task) {
            $this->metricsService->calculateTaskMetrics($task->company_id, $task->project_id);
            if ($task->project_id) {
                $this->metricsService->calculateProjectMetrics($task->project_id);
            }

            $this->metricsService->invalidateCache($task->company_id, auth('api')->id(), $task->project_id);
        }
    }
}
