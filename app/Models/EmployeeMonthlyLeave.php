<?php

namespace App\Models;

use App\Casts\Hash;
use App\Models\BaseModel;
use App\Scopes\CompanyScope;

class EmployeeMonthlyLeave extends BaseModel
{
    protected $table = 'employee_monthly_leave';

    protected $default = ['xid', 'x_employee_id', 'credited_date', 'status', 'used_date', 'x_used_in_leave_id'];

    protected $filterable = ['id', 'status'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'employee_id', 'used_in_leave_id'];

    protected $appends = ['xid', 'x_employee_id', 'x_used_in_leave_id'];

    protected $hashableGetterFunctions = [
        'getXEmployeeIdAttribute' => 'employee_id',
        'getXUsedInLeaveIdAttribute' => 'used_in_leave_id',
    ];

    protected $casts = [
        'employee_id' => Hash::class . ':hash',
        'used_in_leave_id' => Hash::class . ':hash',
        'credited_date' => 'date',
        'used_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CompanyScope);
    }

    public function employee()
    {
        return $this->belongsTo(StaffMember::class, 'employee_id', 'id');
    }

    public function used_in_leave()
    {
        return $this->belongsTo(Leave::class, 'used_in_leave_id', 'id');
    }
}
