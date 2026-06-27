<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Models\User;
use App\Scopes\CompanyScope;
use App\Casts\Hash;

class RecurringTask extends BaseModel
{
    protected $table = 'ep_recurring_tasks';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id', 'task_id', 'created_by'];

    protected $appends = ['xid', 'x_company_id', 'x_task_id', 'x_created_by'];

    protected $hashableGetterFunctions = [
        'getXCompanyIdAttribute' => 'company_id',
        'getXTaskIdAttribute' => 'task_id',
        'getXCreatedByAttribute' => 'created_by',
    ];

    protected $casts = [
        'company_id' => Hash::class . ':hash',
        'task_id' => Hash::class . ':hash',
        'created_by' => Hash::class . ':hash',
        'next_run_at' => 'datetime',
        'end_date' => 'date',
        'interval_value' => 'integer',
        'occurrences' => 'integer',
        'completed_occurrences' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
