<?php

namespace App\Http\Controllers\Api\Self;

use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\Self\Task\IndexRequest;
use App\Http\Requests\Api\Self\Task\StoreRequest;
use App\Http\Requests\Api\Self\Task\UpdateRequest;
use App\Models\Task;

class TaskController extends ApiBaseController
{
    protected $model = Task::class;

    protected $indexRequest  = IndexRequest::class;
    protected $storeRequest  = StoreRequest::class;
    protected $updateRequest = UpdateRequest::class;

    protected function modifyIndex($query)
    {
        $user = user();

        // Scope tasks to those where the user is an assignee, a follower, or the creator
        return $query->where(function ($q) use ($user) {
            $q->whereJsonContains('assignees', $user->xid)
              ->orWhereJsonContains('followers', $user->xid)
              ->orWhere('created_by', $user->id);
        });
    }

    public function storing($task)
    {
        $loggedUser = user();
        $task->created_by = $loggedUser->id;

        return $task;
    }
}
