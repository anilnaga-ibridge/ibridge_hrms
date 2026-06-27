<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Models\User;
use App\Casts\Hash;

class UndoAction extends BaseModel
{
    protected $table = 'ep_undo_actions';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'user_id'];

    protected $appends = ['xid', 'x_user_id'];

    protected $hashableGetterFunctions = [
        'getXUserIdAttribute' => 'user_id',
    ];

    protected $casts = [
        'user_id' => Hash::class . ':hash',
        'payload' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
