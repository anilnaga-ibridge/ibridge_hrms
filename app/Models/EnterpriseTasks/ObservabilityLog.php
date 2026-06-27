<?php

namespace App\Models\EnterpriseTasks;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class ObservabilityLog extends Model
{
    protected $table = 'ep_observability_logs';
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $hidden = ['id'];
    protected $appends = ['xid'];

    protected $casts = [
        'context' => 'array',
    ];

    public function getXidAttribute()
    {
        return Hashids::encode($this->id);
    }
}
