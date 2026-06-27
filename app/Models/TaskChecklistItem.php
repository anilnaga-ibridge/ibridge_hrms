<?php

namespace App\Models;

use App\Casts\Hash;
use App\Models\BaseModel;
use App\Scopes\CompanyScope;

class TaskChecklistItem extends BaseModel
{
    protected $table = 'task_checklist_items';

    protected $default = ['xid', 'name', 'is_completed', 'sort_order'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id', 'task_id', 'created_by'];

    protected $appends = ['xid', 'x_company_id', 'x_task_id', 'x_created_by'];

    protected $filterable = ['name'];

    protected $hashableGetterFunctions = [
        'getXCompanyIdAttribute' => 'company_id',
        'getXTaskIdAttribute' => 'task_id',
        'getXCreatedByAttribute' => 'created_by',
    ];

    protected $casts = [
        'company_id' => Hash::class . ':hash',
        'task_id' => Hash::class . ':hash',
        'created_by' => Hash::class . ':hash',
        'is_completed' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CompanyScope);
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }
}
