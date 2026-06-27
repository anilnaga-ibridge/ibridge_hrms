<?php

namespace App\Listeners\EnterpriseTasks;

use App\Services\EnterpriseTasks\AutomationService;

class TriggerAutomations
{
    protected AutomationService $automationService;

    public function __construct(AutomationService $automationService)
    {
        $this->automationService = $automationService;
    }

    public function handle($event): void
    {
        $class = class_basename($event);
        $task = null;
        $eventName = '';

        switch ($class) {
            case 'TaskCreated':
                $task = $event->task;
                $eventName = 'task_created';
                break;
            case 'TaskUpdated':
                $task = $event->task;
                $eventName = 'task_updated';
                break;
            case 'TaskCompleted':
                $task = $event->task;
                $eventName = 'task_completed';
                break;
            case 'TaskStatusChanged':
                $task = $event->task;
                $eventName = 'task_status_changed';
                break;
            case 'TaskAssigned':
                $task = $event->task;
                $eventName = 'task_assigned';
                break;
        }

        if ($task && $eventName) {
            $this->automationService->triggerRules($eventName, $task);
        }
    }
}
