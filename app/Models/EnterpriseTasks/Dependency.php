<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Models\User;
use App\Scopes\CompanyScope;
use App\Casts\Hash;

class Dependency extends BaseModel
{
    protected $table = 'ep_task_dependencies';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id', 'project_id', 'task_id', 'depends_on_task_id', 'created_by'];

    protected $appends = ['xid', 'x_company_id', 'x_project_id', 'x_task_id', 'x_depends_on_task_id', 'x_created_by'];

    protected $hashableGetterFunctions = [
        'getXCompanyIdAttribute' => 'company_id',
        'getXProjectIdAttribute' => 'project_id',
        'getXTaskIdAttribute' => 'task_id',
        'getXDependsOnTaskIdAttribute' => 'depends_on_task_id',
        'getXCreatedByAttribute' => 'created_by',
    ];

    protected $casts = [
        'company_id' => Hash::class . ':hash',
        'project_id' => Hash::class . ':hash',
        'task_id' => Hash::class . ':hash',
        'depends_on_task_id' => Hash::class . ':hash',
        'created_by' => Hash::class . ':hash',
        'lag_days' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function dependsOnTask()
    {
        return $this->belongsTo(Task::class, 'depends_on_task_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
