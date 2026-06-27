<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Casts\Hash;

class ChecklistItem extends BaseModel
{
    protected $table = 'ep_checklist_items';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'checklist_id'];

    protected $appends = ['xid', 'x_checklist_id'];

    protected $hashableGetterFunctions = [
        'getXChecklistIdAttribute' => 'checklist_id',
    ];

    protected $casts = [
        'checklist_id' => Hash::class . ':hash',
        'is_completed' => 'boolean',
    ];

    public function checklist()
    {
        return $this->belongsTo(Checklist::class, 'checklist_id');
    }
}
