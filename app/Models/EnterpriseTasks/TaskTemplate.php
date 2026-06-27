<?php

namespace App\Models\EnterpriseTasks;

use App\Models\BaseModel;
use App\Models\User;
use App\Scopes\CompanyScope;
use App\Casts\Hash;

class TaskTemplate extends BaseModel
{
    protected $table = 'ep_task_templates';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'company_id', 'created_by'];

    protected $appends = ['xid', 'x_company_id', 'x_created_by', 'items'];

    protected $hashableGetterFunctions = [
        'getXCompanyIdAttribute' => 'company_id',
        'getXCreatedByAttribute' => 'created_by',
    ];

    protected $casts = [
        'company_id' => Hash::class . ':hash',
        'created_by' => Hash::class . ':hash',
        'is_global' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(TaskTemplateItem::class, 'template_id')->orderBy('sort_order', 'asc');
    }

    public function getItemsAttribute()
    {
        return $this->items()->get();
    }
}
