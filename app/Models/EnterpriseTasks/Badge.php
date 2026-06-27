<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;

class Badge extends BaseModel
{
    protected $table = 'ep_badges';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id'];

    protected $appends = ['xid'];

    protected $casts = [
        'requirement_value' => 'integer',
    ];
}
