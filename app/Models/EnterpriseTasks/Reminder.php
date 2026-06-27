<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Models\User;
use App\Casts\Hash;

class Reminder extends BaseModel
{
    protected $table = 'ep_reminders';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'task_id', 'user_id'];

    protected $appends = ['xid', 'x_task_id', 'x_user_id'];

    protected $hashableGetterFunctions = [
        'getXTaskIdAttribute' => 'task_id',
        'getXUserIdAttribute' => 'user_id',
    ];

    protected $casts = [
        'task_id' => Hash::class . ':hash',
        'user_id' => Hash::class . ':hash',
        'remind_at' => 'datetime',
        'is_sent' => 'boolean',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
