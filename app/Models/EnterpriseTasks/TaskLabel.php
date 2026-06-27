<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Casts\Hash;

class TaskLabel extends BaseModel
{
    protected $table = 'ep_task_labels';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'task_id', 'label_id'];

    protected $appends = ['xid', 'x_task_id', 'x_label_id'];

    protected $hashableGetterFunctions = [
        'getXTaskIdAttribute' => 'task_id',
        'getXLabelIdAttribute' => 'label_id',
    ];

    protected $casts = [
        'task_id' => Hash::class . ':hash',
        'label_id' => Hash::class . ':hash',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function label()
    {
        return $this->belongsTo(Label::class, 'label_id');
    }
}
