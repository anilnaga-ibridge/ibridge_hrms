<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\Project\IndexRequest;
use App\Http\Requests\Api\Project\StoreRequest;
use App\Http\Requests\Api\Project\UpdateRequest;
use App\Http\Requests\Api\Project\DeleteRequest;
use App\Models\Project;

class ProjectController extends ApiBaseController
{
    protected $model = Project::class;

    protected $indexRequest = IndexRequest::class;
    protected $storeRequest = StoreRequest::class;
    protected $updateRequest = UpdateRequest::class;
    protected $deleteRequest = DeleteRequest::class;

    public function storing($project)
    {
        $loggedUser = user();
        $project->created_by = $loggedUser->id;

        return $project;
    }
}
