<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Scopes\CompanyScope;

class RotationalSchedule extends BaseModel
{
    protected $table = 'rotational_schedules';

    protected $default = ['xid', 'x_rotational_team_id', 'date', 'is_weekoff'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'rotational_team_id', 'company_id'];

    protected $appends = ['xid', 'x_rotational_team_id'];

    protected $hashableGetterFunctions = [
        'getXRotationalTeamIdAttribute' => 'rotational_team_id',
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
}
