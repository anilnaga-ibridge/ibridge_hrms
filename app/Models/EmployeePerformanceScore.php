<?php

namespace App\Models;

use App\Casts\Hash;
use App\Models\BaseModel;
use App\Scopes\CompanyScope;

class EmployeePerformanceScore extends BaseModel
{
    protected $table = 'employee_performance_scores';

    protected $default = ['xid', 'user_id', 'month', 'year', 'overall_score', 'grade'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'user_id'];

    protected $appends = ['xid', 'x_user_id'];

    protected $filterable = ['user_id', 'month', 'year', 'grade'];

    protected $hashableGetterFunctions = [
        'getXUserIdAttribute' => 'user_id',
    ];

    protected $casts = [
        'user_id' => Hash::class . ':hash',
        'month' => 'integer',
        'year' => 'integer',
        'attendance_score' => 'float',
        'productivity_score' => 'float',
        'communication_score' => 'float',
        'leadership_score' => 'float',
        'discipline_score' => 'float',
        'teamwork_score' => 'float',
        'task_completion_score' => 'float',
        'overall_score' => 'float',
        'department_rank' => 'integer',
        'company_rank' => 'integer',
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
}
