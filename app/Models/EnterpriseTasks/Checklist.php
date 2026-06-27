<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Casts\Hash;

class Checklist extends BaseModel
{
    protected $table = 'ep_checklists';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'task_id'];

    protected $appends = ['xid', 'x_task_id', 'items', 'completion_percentage'];

    protected $hashableGetterFunctions = [
        'getXTaskIdAttribute' => 'task_id',
    ];

    protected $casts = [
        'task_id' => Hash::class . ':hash',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function items()
    {
        return $this->hasMany(ChecklistItem::class, 'checklist_id')->orderBy('sort_order', 'asc');
    }

    public function getItemsAttribute()
    {
        return $this->items()->get();
    }

    public function getCompletionPercentageAttribute()
    {
        $total = $this->items()->count();
        if ($total === 0) {
            return 0;
        }
        $completed = $this->items()->where('is_completed', true)->count();
        return round(($completed / $total) * 100);
    }
}
