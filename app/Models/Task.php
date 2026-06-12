<?php

namespace App\Models;

use App\Casts\Hash;
use App\Models\BaseModel;
use App\Scopes\CompanyScope;
use App\Models\User;
use App\Classes\Common;

class Task extends BaseModel
{
    protected $table = 'tasks';

    protected $default = ['xid', 'name', 'status', 'priority', 'start_date', 'due_date', 'description', 'project_id', 'assignees', 'assignee_details', 'is_public', 'is_billable', 'task_file', 'task_file_url', 'hourly_rate', 'repeat_every', 'followers', 'follower_details', 'tags'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id', 'project_id', 'created_by'];

    protected $appends = ['xid', 'x_company_id', 'x_project_id', 'x_created_by', 'assignee_details', 'follower_details'];

    protected $filterable = ['name', 'status', 'priority', 'project_id'];

    protected $hashableGetterFunctions = [
        'getXCompanyIdAttribute' => 'company_id',
        'getXProjectIdAttribute' => 'project_id',
        'getXCreatedByAttribute' => 'created_by',
    ];

    protected $casts = [
        'company_id' => Hash::class . ':hash',
        'project_id' => Hash::class . ':hash',
        'created_by' => Hash::class . ':hash',
        'assignees' => 'array',
        'followers' => 'array',
        'tags' => 'array',
        'is_public' => 'boolean',
        'is_billable' => 'boolean',
        'hourly_rate' => 'double',
        'start_date' => 'date',
        'due_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CompanyScope);
    }

    public function getAssigneeDetailsAttribute()
    {
        if (!$this->assignees || !is_array($this->assignees)) {
            return [];
        }

        $userIds = [];
        foreach ($this->assignees as $hashId) {
            $userIds[] = Common::getIdFromHash($hashId);
        }

        return User::select('id', 'name', 'profile_image')
            ->whereIn('id', $userIds)
            ->get()
            ->map(function ($u) {
                return [
                    'xid' => $u->xid,
                    'name' => $u->name,
                    'profile_image_url' => $u->profile_image_url
                ];
            });
    }

    public function getFollowerDetailsAttribute()
    {
        if (!$this->followers || !is_array($this->followers)) {
            return [];
        }

        $userIds = [];
        foreach ($this->followers as $hashId) {
            $userIds[] = Common::getIdFromHash($hashId);
        }

        return User::select('id', 'name', 'profile_image')
            ->whereIn('id', $userIds)
            ->get()
            ->map(function ($u) {
                return [
                    'xid' => $u->xid,
                    'name' => $u->name,
                    'profile_image_url' => $u->profile_image_url
                ];
            });
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(StaffMember::class, 'created_by', 'id');
    }
}
