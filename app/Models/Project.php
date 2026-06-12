<?php

namespace App\Models;

use App\Casts\Hash;
use App\Models\BaseModel;
use App\Scopes\CompanyScope;
use App\Models\User;
use App\Classes\Common;

class Project extends BaseModel
{
    protected $table = 'projects';

    protected $default = ['xid', 'name', 'status', 'start_date', 'deadline', 'description', 'members', 'member_details', 'customer', 'calculate_progress', 'progress', 'billing_type', 'total_rate', 'estimated_hours', 'tags', 'send_email'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id', 'created_by'];

    protected $appends = ['xid', 'x_company_id', 'x_created_by', 'member_details'];

    protected $filterable = ['name', 'status', 'customer'];

    protected $hashableGetterFunctions = [
        'getXCompanyIdAttribute' => 'company_id',
        'getXCreatedByAttribute' => 'created_by',
    ];

    protected $casts = [
        'company_id' => Hash::class . ':hash',
        'created_by' => Hash::class . ':hash',
        'members' => 'array',
        'start_date' => 'date',
        'deadline' => 'date',
        'calculate_progress' => 'boolean',
        'progress' => 'integer',
        'total_rate' => 'double',
        'estimated_hours' => 'double',
        'send_email' => 'boolean',
        'tags' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CompanyScope);
    }

    public function getMemberDetailsAttribute()
    {
        if (!$this->members || !is_array($this->members)) {
            return [];
        }

        $userIds = [];
        foreach ($this->members as $hashId) {
            $userIds[] = Common::getIdFromHash($hashId);
        }

        return User::select('id', 'name', 'profile_image')
            ->whereIn('id', $userIds)
            ->get()
            ->map(function ($u) {
                return [
                    'xid' => $u->xid,
                    'name' => $u->name,
                    'profile_image_url' => $u->profile_image_url
                ];
            });
    }

    public function creator()
    {
        return $this->belongsTo(StaffMember::class, 'created_by', 'id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'project_id', 'id');
    }
}
