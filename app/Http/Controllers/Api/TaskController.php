<?php

namespace App\Http\Controllers\Api;

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
}
