<?php

namespace App\Models;

use App\Casts\Hash;
use App\Models\BaseModel;
use App\Scopes\CompanyScope;

class TaskComment extends BaseModel
{
    protected $table = 'task_comments';

    protected $default = ['xid', 'comment', 'created_at', 'user'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id', 'task_id', 'created_by'];

    protected $appends = ['xid', 'x_company_id', 'x_task_id', 'x_created_by', 'user'];

    protected $filterable = ['comment'];

    protected $hashableGetterFunctions = [
        'getXCompanyIdAttribute' => 'company_id',
        'getXTaskIdAttribute' => 'task_id',
        'getXCreatedByAttribute' => 'created_by',
    ];

    protected $casts = [
        'company_id' => Hash::class . ':hash',
        'task_id' => Hash::class . ':hash',
        'created_by' => Hash::class . ':hash',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CompanyScope);
    }

    public function getUserAttribute()
    {
        $user = User::find($this->created_by);
        if ($user) {
            return [
                'xid' => $user->xid,
                'name' => $user->name,
                'profile_image_url' => $user->profile_image_url,
            ];
        }
        return null;
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }
}
