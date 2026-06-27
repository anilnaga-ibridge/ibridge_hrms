<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Models\User;
use App\Casts\Hash;

class TaskComment extends BaseModel
{
    protected $table = 'ep_task_comments';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'task_id', 'user_id', 'parent_id'];

    protected $appends = ['xid', 'x_task_id', 'x_user_id', 'x_parent_id', 'user', 'reactions', 'replies'];

    protected $hashableGetterFunctions = [
        'getXTaskIdAttribute' => 'task_id',
        'getXUserIdAttribute' => 'user_id',
        'getXParentIdAttribute' => 'parent_id',
    ];

    protected $casts = [
        'task_id' => Hash::class . ':hash',
        'user_id' => Hash::class . ':hash',
        'parent_id' => Hash::class . ':hash',
        'is_pinned' => 'boolean',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function commenter()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getUserAttribute()
    {
        $u = $this->commenter;
        return $u ? [
            'xid' => $u->xid,
            'name' => $u->name,
            'profile_image_url' => $u->profile_image_url
        ] : null;
    }

    public function commentReactions()
    {
        return $this->hasMany(CommentReaction::class, 'comment_id');
    }

    public function getReactionsAttribute()
    {
        return $this->commentReactions()->get()->groupBy('emoji')->map(function($items, $emoji) {
            return [
                'emoji' => $emoji,
                'count' => count($items),
                'users' => $items->map(function($reaction) {
                    return $reaction->user ? [
                        'xid' => $reaction->user->xid,
                        'name' => $reaction->user->name
                    ] : null;
                })->filter()->values()
            ];
        })->values();
    }

    public function replies()
    {
        return $this->hasMany(TaskComment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    public function getRepliesAttribute()
    {
        return $this->replies()->get();
    }
}
