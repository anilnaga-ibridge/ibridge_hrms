<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Scopes\CompanyScope;
use App\Casts\Hash;

class WorkspaceSetting extends BaseModel
{
    protected $table = 'ep_workspace_settings';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id'];

    protected $appends = ['xid', 'x_company_id'];

    protected $hashableGetterFunctions = [
        'getXCompanyIdAttribute' => 'company_id',
    ];

    protected $casts = [
        'company_id' => Hash::class . ':hash',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);
    }
}
