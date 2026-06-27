<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Models\User;
use App\Scopes\CompanyScope;
use App\Casts\Hash;

class Favorite extends BaseModel
{
    protected $table = 'ep_favorites';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id', 'user_id'];

    protected $appends = ['xid', 'x_company_id', 'x_user_id'];

    protected $hashableGetterFunctions = [
        'getXCompanyIdAttribute' => 'company_id',
        'getXUserIdAttribute' => 'user_id',
    ];

    protected $casts = [
        'company_id' => Hash::class . ':hash',
        'user_id' => Hash::class . ':hash',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
