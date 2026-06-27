<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Models\User;
use App\Casts\Hash;

class TaskActivity extends BaseModel
{
    protected $table = 'ep_task_activities';

    public $timestamps = false;

    protected $guarded = ['id', 'created_at'];

    protected $hidden = ['id', 'task_id', 'user_id'];

    protected $appends = ['xid', 'x_task_id', 'x_user_id', 'user'];

    protected $hashableGetterFunctions = [
        'getXTaskIdAttribute' => 'task_id',
        'getXUserIdAttribute' => 'user_id',
    ];

    protected $casts = [
        'task_id' => Hash::class . ':hash',
        'user_id' => Hash::class . ':hash',
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getUserAttribute()
    {
        $u = $this->getRelationValue('user');
        return $u ? [
            'xid' => $u->xid,
            'name' => $u->name,
            'profile_image_url' => $u->profile_image_url
        ] : null;
    }
}
