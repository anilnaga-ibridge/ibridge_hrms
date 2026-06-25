<?php

namespace App\Http\Controllers\Api;

use App\Classes\Notify;
use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\Task\IndexRequest;
use App\Http\Requests\Api\Task\StoreRequest;
use App\Http\Requests\Api\Task\UpdateRequest;
use App\Http\Requests\Api\Task\DeleteRequest;
use App\Models\Task;

class TaskController extends ApiBaseController
{
    protected $model = Task::class;

    protected $indexRequest = IndexRequest::class;
    protected $storeRequest = StoreRequest::class;
    protected $updateRequest = UpdateRequest::class;
    protected $deleteRequest = DeleteRequest::class;

    public function storing($task)
    {
        $loggedUser = user();
        $task->created_by = $loggedUser->id;

        return $task;
    }

    public function stored($task)
    {
        $this->notifyAssignees($task);
        return $task;
    }

    public function updated($task)
    {
        $this->notifyAssignees($task);
        return $task;
    }

    protected function notifyAssignees($task)
    {
        if ($task->assignees && is_array($task->assignees)) {
            foreach ($task->assignees as $hashedUserId) {
                $userId = $this->getIdFromHash($hashedUserId);
                if ($userId) {
                    $notificationData = [
                        'task_id' => $task->id,
                        'recipient_id' => $userId,
                    ];
                    Notify::send('employee_task_assigned', $notificationData);
                }
            }
        }
    }
}
