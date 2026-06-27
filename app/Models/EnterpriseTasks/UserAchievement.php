<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Models\User;
use App\Scopes\CompanyScope;
use App\Casts\Hash;

class UserAchievement extends BaseModel
{
    protected $table = 'ep_user_achievements';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id', 'user_id', 'badge_id'];

    protected $appends = ['xid', 'x_company_id', 'x_user_id', 'x_badge_id'];

    protected $hashableGetterFunctions = [
        'getXCompanyIdAttribute' => 'company_id',
        'getXUserIdAttribute' => 'user_id',
        'getXBadgeIdAttribute' => 'badge_id',
    ];

    protected $casts = [
        'company_id' => Hash::class . ':hash',
        'user_id' => Hash::class . ':hash',
        'badge_id' => Hash::class . ':hash',
        'unlocked_at' => 'datetime',
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

    public function badge()
    {
        return $this->belongsTo(Badge::class, 'badge_id');
    }
}
