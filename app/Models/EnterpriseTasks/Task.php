<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Models\User;
use App\Scopes\CompanyScope;
use App\Casts\Hash;
use App\Models\Project;

class Task extends BaseModel
{
    protected $table = 'ep_tasks';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = [
        'id', 'company_id', 'project_id', 'section_id', 'parent_id', 'created_by', 'updated_by'
    ];

    protected $appends = [
        'xid', 'x_company_id', 'x_project_id', 'x_section_id', 'x_parent_id', 'x_created_by', 'x_updated_by',
        'assignees', 'reviewers', 'watchers', 'labels', 'completion_percentage'
    ];

    protected $hashableGetterFunctions = [
        'getXCompanyIdAttribute' => 'company_id',
        'getXProjectIdAttribute' => 'project_id',
        'getXSectionIdAttribute' => 'section_id',
        'getXParentIdAttribute' => 'parent_id',
        'getXCreatedByAttribute' => 'created_by',
        'getXUpdatedByAttribute' => 'updated_by',
    ];

    protected $casts = [
        'company_id' => Hash::class . ':hash',
        'project_id' => Hash::class . ':hash',
        'section_id' => Hash::class . ':hash',
        'parent_id' => Hash::class . ':hash',
        'created_by' => Hash::class . ':hash',
        'updated_by' => Hash::class . ':hash',
        'due_date' => 'date',
        'start_date' => 'date',
        'is_archived' => 'boolean',
        'is_deleted' => 'boolean',
        'estimated_hours' => 'float',
        'actual_hours' => 'float',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function section()
    {
        return $this->belongsTo(ProjectSection::class, 'section_id');
    }

    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function subtasks()
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('id', 'asc');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function taskUsers()
    {
        return $this->hasMany(TaskUser::class, 'task_id');
    }

    public function getAssigneesAttribute()
    {
        return $this->taskUsers()->where('type', 'assignee')->with('user')->get()->map(function($tu) {
            return $tu->user ? [
                'xid' => $tu->user->xid,
                'name' => $tu->user->name,
                'profile_image_url' => $tu->user->profile_image_url,
            ] : null;
        })->filter()->values();
    }

    public function getReviewersAttribute()
    {
        return $this->taskUsers()->where('type', 'reviewer')->with('user')->get()->map(function($tu) {
            return $tu->user ? [
                'xid' => $tu->user->xid,
                'name' => $tu->user->name,
                'profile_image_url' => $tu->user->profile_image_url,
            ] : null;
        })->filter()->values();
    }

    public function getWatchersAttribute()
    {
        return $this->taskUsers()->where('type', 'watcher')->with('user')->get()->map(function($tu) {
            return $tu->user ? [
                'xid' => $tu->user->xid,
                'name' => $tu->user->name,
                'profile_image_url' => $tu->user->profile_image_url,
            ] : null;
        })->filter()->values();
    }

    public function taskLabels()
    {
        return $this->hasMany(TaskLabel::class, 'task_id');
    }

    public function getLabelsAttribute()
    {
        return $this->taskLabels()->with('label')->get()->map(function($tl) {
            return $tl->label ? [
                'xid' => $tl->label->xid,
                'name' => $tl->label->name,
                'color' => $tl->label->color,
            ] : null;
        })->filter()->values();
    }

    public function checklists()
    {
        return $this->hasMany(Checklist::class, 'task_id')->orderBy('sort_order', 'asc');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id')->whereNull('parent_id')->orderBy('is_pinned', 'desc')->orderBy('created_at', 'desc');
    }

    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class, 'task_id')->orderBy('created_at', 'desc');
    }

    public function activities()
    {
        return $this->hasMany(TaskActivity::class, 'task_id')->orderBy('created_at', 'desc');
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class, 'task_id');
    }

    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class, 'task_id')->orderBy('start_time', 'desc');
    }

    public function getCompletionPercentageAttribute()
    {
        // For parent tasks with subtasks, calculate percentage based on subtask completion status.
        // For individual tasks, calculate based on checklist items.
        // Otherwise, 0 or 100 if completed.
        if ($this->subtasks()->count() > 0) {
            $totalSub = $this->subtasks()->count();
            $completedSub = $this->subtasks()->where('status', 'completed')->count();
            return round(($completedSub / $totalSub) * 100);
        }

        // Checklist completion percentage
        $totalChecklistItems = ChecklistItem::whereIn('checklist_id', $this->checklists()->pluck('id'))->count();
        if ($totalChecklistItems > 0) {
            $completedChecklistItems = ChecklistItem::whereIn('checklist_id', $this->checklists()->pluck('id'))
                ->where('is_completed', true)->count();
            return round(($completedChecklistItems / $totalChecklistItems) * 100);
        }

        return $this->status === 'completed' ? 100 : 0;
    }
}
