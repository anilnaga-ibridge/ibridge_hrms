<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Models\User;
use App\Casts\Hash;

class CommentReaction extends BaseModel
{
    protected $table = 'ep_comment_reactions';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'comment_id', 'user_id'];

    protected $appends = ['xid', 'x_comment_id', 'x_user_id'];

    protected $hashableGetterFunctions = [
        'getXCommentIdAttribute' => 'comment_id',
        'getXUserIdAttribute' => 'user_id',
    ];

    protected $casts = [
        'comment_id' => Hash::class . ':hash',
        'user_id' => Hash::class . ':hash',
    ];

    public function comment()
    {
        return $this->belongsTo(TaskComment::class, 'comment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
