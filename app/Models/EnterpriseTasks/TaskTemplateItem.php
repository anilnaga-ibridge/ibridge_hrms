<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Casts\Hash;

class TaskTemplateItem extends BaseModel
{
    protected $table = 'ep_task_template_items';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'template_id'];

    protected $appends = ['xid', 'x_template_id'];

    protected $hashableGetterFunctions = [
        'getXTemplateIdAttribute' => 'template_id',
    ];

    protected $casts = [
        'template_id' => Hash::class . ':hash',
        'estimated_hours' => 'float',
        'sort_order' => 'integer'
    ];

    public function template()
    {
        return $this->belongsTo(TaskTemplate::class, 'template_id');
    }
}
