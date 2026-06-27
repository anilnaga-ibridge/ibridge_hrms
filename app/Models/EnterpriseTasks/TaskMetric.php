<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Scopes\CompanyScope;
use App\Casts\Hash;

class TaskMetric extends BaseModel
{
    protected $table = 'ep_task_metrics';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id', 'project_id'];

    protected $appends = ['xid', 'x_company_id', 'x_project_id'];

    protected $hashableGetterFunctions = [
        'getXCompanyIdAttribute' => 'company_id',
        'getXProjectIdAttribute' => 'project_id',
    ];

    protected $casts = [
        'company_id' => Hash::class . ':hash',
        'project_id' => Hash::class . ':hash',
        'date' => 'date',
        'total_tasks' => 'integer',
        'completed_tasks' => 'integer',
        'pending_tasks' => 'integer',
        'overdue_tasks' => 'integer',
        'time_spent_minutes' => 'integer'
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
}
