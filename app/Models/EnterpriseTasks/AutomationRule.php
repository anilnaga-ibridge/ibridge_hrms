<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Models\User;
use App\Scopes\CompanyScope;
use App\Casts\Hash;

class AutomationRule extends BaseModel
{
    protected $table = 'ep_automation_rules';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id', 'project_id', 'created_by'];

    protected $appends = ['xid', 'x_company_id', 'x_project_id', 'x_created_by'];

    protected $hashableGetterFunctions = [
        'getXCompanyIdAttribute' => 'company_id',
        'getXProjectIdAttribute' => 'project_id',
        'getXCreatedByAttribute' => 'created_by',
    ];

    protected $casts = [
        'company_id' => Hash::class . ':hash',
        'project_id' => Hash::class . ':hash',
        'created_by' => Hash::class . ':hash',
        'conditions' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean'
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
