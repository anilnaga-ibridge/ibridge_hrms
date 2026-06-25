<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Scopes\CompanyScope;

class LeaveType extends BaseModel
{
    protected $table = 'leave_types';

    protected $default = ['xid', 'name', 'is_paid', 'total_leaves', 'is_monthly_leave', 'monthly_leave_expiry_cycle', 'count_type', 'max_leaves_per_month', 'created_by'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id',];

    protected $appends = ['xid',];

    protected $filterable = ['name'];

    protected $casts = [
        'is_deletable' => 'integer',
        'is_paid' => 'integer',
        'is_monthly_leave' => 'boolean',
        'max_leaves_per_month' => 'integer',
        'total_leaves' => 'integer',
        'monthly_leave_expiry_cycle' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CompanyScope);
    }

    public function employeeSpecificLeaveCount()
    {
        return $this->hasMany(EmployeeSpecificLeaveCount::class, 'leave_type_id', 'id');
    }

    public function isMonthlyLeave()
    {
        return (bool) $this->is_monthly_leave;
    }
}

