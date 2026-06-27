<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Casts\Hash;
use App\Models\Project;

class ProjectSection extends BaseModel
{
    protected $table = 'ep_project_sections';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'project_id'];

    protected $appends = ['xid', 'x_project_id'];

    protected $hashableGetterFunctions = [
        'getXProjectIdAttribute' => 'project_id',
    ];

    protected $casts = [
        'project_id' => Hash::class . ':hash',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'section_id')->orderBy('sort_order', 'asc');
    }
}
