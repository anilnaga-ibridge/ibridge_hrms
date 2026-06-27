<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\TaskChecklistItem\IndexRequest;
use App\Http\Requests\Api\TaskChecklistItem\StoreRequest;
use App\Http\Requests\Api\TaskChecklistItem\UpdateRequest;
use App\Http\Requests\Api\TaskChecklistItem\DeleteRequest;
use App\Models\TaskChecklistItem;

class TaskChecklistItemController extends ApiBaseController
{
    protected $model = TaskChecklistItem::class;

    protected $indexRequest = IndexRequest::class;
    protected $storeRequest = StoreRequest::class;
    protected $updateRequest = UpdateRequest::class;
    protected $deleteRequest = DeleteRequest::class;

    public function storing($item)
    {
        $loggedUser = user();
        $item->created_by = $loggedUser->id;

        return $item;
    }
}
