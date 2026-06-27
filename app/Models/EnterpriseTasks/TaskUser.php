<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Models\User;
use App\Casts\Hash;

class TaskUser extends BaseModel
{
    protected $table = 'ep_task_users';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'task_id', 'user_id', 'assigned_by'];

    protected $appends = ['xid', 'x_task_id', 'x_user_id', 'x_assigned_by'];

    protected $hashableGetterFunctions = [
        'getXTaskIdAttribute' => 'task_id',
        'getXUserIdAttribute' => 'user_id',
        'getXAssignedByAttribute' => 'assigned_by',
    ];

    protected $casts = [
        'task_id' => Hash::class . ':hash',
        'user_id' => Hash::class . ':hash',
        'assigned_by' => Hash::class . ':hash',
        'assigned_date' => 'datetime',
        'accepted_date' => 'datetime',
        'completed_date' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
