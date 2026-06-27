<?php

namespace App\Http\Controllers\Api\Self;

use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\TaskComment\IndexRequest;
use App\Http\Requests\Api\TaskComment\StoreRequest;
use App\Http\Requests\Api\TaskComment\UpdateRequest;
use App\Http\Requests\Api\TaskComment\DeleteRequest;
use App\Models\TaskComment;

class TaskCommentController extends ApiBaseController
{
    protected $model = TaskComment::class;

    protected $indexRequest = IndexRequest::class;
    protected $storeRequest = StoreRequest::class;
    protected $updateRequest = UpdateRequest::class;
    protected $deleteRequest = DeleteRequest::class;

    public function storing($comment)
    {
        $loggedUser = user();
        $comment->created_by = $loggedUser->id;

        return $comment;
    }

    protected function modifyIndex($query)
    {
        $user = user();

        return $query->whereHas('task', function ($q) use ($user) {
            $q->where(function ($q2) use ($user) {
                $q2->whereJsonContains('assignees', $user->xid)
                  ->orWhereJsonContains('followers', $user->xid)
                  ->orWhere('created_by', $user->id);
            });
        });
    }
}
