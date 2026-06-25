<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Scopes\CompanyScope;

class RotationalTeam extends BaseModel
{
    protected $table = 'rotational_teams';

    protected $default = ['xid', 'name', 'rotation_order', 'is_active'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id'];

    protected $appends = ['xid', 'members_count'];

    protected $filterable = ['name'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);

        static::saving(function ($model) {
            $company = company();
            if ($company) {
                $model->company_id = $company->id;
            }
        });
    }

    public function members()
    {
        return $this->hasMany(RotationalTeamMember::class, 'rotational_team_id', 'id');
    }

    public function schedules()
    {
        return $this->hasMany(RotationalSchedule::class, 'rotational_team_id', 'id');
    }

    public function getMembersCountAttribute()
    {
        if (array_key_exists('members_count', $this->attributes)) {
            return (int) $this->attributes['members_count'];
        }
        return $this->members()->count();
    }
}
