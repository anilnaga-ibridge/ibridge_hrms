<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Models\User;
use App\Casts\Hash;

class PomodoroSession extends BaseModel
{
    protected $table = 'ep_pomodoro_sessions';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'user_id', 'task_id'];

    protected $appends = ['xid', 'x_user_id', 'x_task_id'];

    protected $hashableGetterFunctions = [
        'getXUserIdAttribute' => 'user_id',
        'getXTaskIdAttribute' => 'task_id',
    ];

    protected $casts = [
        'user_id' => Hash::class . ':hash',
        'task_id' => Hash::class . ':hash',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'duration' => 'integer',
        'completed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
