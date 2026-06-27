<?php

namespace App\Models\EnterpriseTasks;

use Illuminate\Database\Eloquent\Model;
use App\Casts\Hash;
use Vinkla\Hashids\Facades\Hashids;

class FeatureFlag extends Model
{
    protected $table = 'ep_feature_flags';
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $hidden = ['id', 'company_id'];
    protected $appends = ['xid'];

    protected $casts = [
        'is_enabled' => 'boolean',
        'config'     => 'array',
    ];

    public function getXidAttribute()
    {
        return Hashids::encode($this->id);
    }
}
