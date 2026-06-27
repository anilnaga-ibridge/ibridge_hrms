<?php

namespace App\Models\EnterpriseTasks;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Scopes\CompanyScope;
use Vinkla\Hashids\Facades\Hashids;

class SyncQueue extends Model
{
    protected $table = 'ep_sync_queue';
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $hidden = ['id', 'company_id', 'user_id'];
    protected $appends = ['xid'];

    protected $casts = [
        'payload'           => 'array',
        'client_created_at' => 'datetime',
        'applied_at'        => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);
    }

    public function getXidAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
