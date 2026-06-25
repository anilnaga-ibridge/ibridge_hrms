<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Scopes\CompanyScope;

class RotationalTeamMember extends BaseModel
{
    protected $table = 'rotational_team_members';

    protected $default = ['xid', 'x_rotational_team_id', 'x_user_id'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'rotational_team_id', 'company_id', 'user_id'];

    protected $appends = ['xid', 'x_rotational_team_id', 'x_user_id'];

    protected $hashableGetterFunctions = [
        'getXRotationalTeamIdAttribute' => 'rotational_team_id',
        'getXUserIdAttribute' => 'user_id',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);
    }

    public function team()
    {
        return $this->belongsTo(RotationalTeam::class, 'rotational_team_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(StaffMember::class, 'user_id', 'id');
    }
}
