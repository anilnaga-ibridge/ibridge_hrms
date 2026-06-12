<?php

namespace App\Http\Controllers\Api\Self;

use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\Self\Project\IndexRequest;
use App\Http\Requests\Api\Self\Project\StoreRequest;
use App\Http\Requests\Api\Self\Project\UpdateRequest;
use App\Models\Project;

class ProjectController extends ApiBaseController
{
    protected $model = Project::class;

    protected $indexRequest  = IndexRequest::class;
    protected $storeRequest  = StoreRequest::class;
    protected $updateRequest = UpdateRequest::class;

    protected function modifyIndex($query)
    {
        $user = user();

        // Scope projects to those where the user is a member or the creator
        return $query->where(function ($q) use ($user) {
            $q->whereJsonContains('members', $user->xid)
              ->orWhere('created_by', $user->id);
        });
    }

    public function storing($project)
    {
        $loggedUser = user();
        $project->created_by = $loggedUser->id;

        return $project;
    }
}
