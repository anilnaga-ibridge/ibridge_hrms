<?php

namespace App\Models;

use App\Casts\Hash;
use App\Models\BaseModel;
use App\Scopes\CompanyScope;

class Customer extends BaseModel
{
    protected $table = 'customers';

    protected $default = ['xid', 'name', 'email', 'phone', 'website', 'tax_number', 'address'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id', 'created_by'];

    protected $appends = ['xid', 'x_company_id', 'x_created_by'];

    protected $filterable = ['name', 'email', 'phone'];

    protected $hashableGetterFunctions = [
        'getXCompanyIdAttribute' => 'company_id',
        'getXCreatedByAttribute' => 'created_by',
    ];

    protected $casts = [
        'company_id' => Hash::class . ':hash',
        'created_by' => Hash::class . ':hash',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CompanyScope);
    }
}
