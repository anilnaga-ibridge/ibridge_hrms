<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Scopes\CompanyScope;

class ShiftRoster extends BaseModel
{
    protected $table = 'shift_rosters';

    protected $default = ['xid', 'x_user_id', 'x_shift_id', 'date'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id', 'user_id', 'shift_id'];

    protected $appends = ['xid', 'x_user_id', 'x_shift_id'];

    protected $filterable = ['user_id', 'date'];

    protected $hashableGetterFunctions = [
        'getXUserIdAttribute' => 'user_id',
        'getXShiftIdAttribute' => 'shift_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CompanyScope);
    }

    public function user()
    {
        return $this->belongsTo(StaffMember::class, 'user_id', 'id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'id');
    }
}
