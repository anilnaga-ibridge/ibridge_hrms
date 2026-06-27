<?php

namespace App\DTOs\EnterpriseTasks;

use Illuminate\Http\Request;
use App\Classes\Common;

class AutomationRuleDTO
{
    public ?int $project_id;
    public string $name;
    public ?string $description;
    public string $event_name;
    public ?array $conditions;
    public ?array $actions;
    public bool $is_active;

    public function __construct(array $data)
    {
        $this->project_id = isset($data['x_project_id']) && $data['x_project_id'] ? Common::getIdFromHash($data['x_project_id']) : null;
        $this->name = $data['name'];
        $this->description = $data['description'] ?? null;
        $this->event_name = $data['event_name'];
        $this->conditions = $data['conditions'] ?? null;
        $this->actions = $data['actions'] ?? null;
        $this->is_active = $data['is_active'] ?? true;
    }

    public static function fromRequest(Request $request): self
    {
        return new self($request->all());
    }

    public function toArray(): array
    {
        return [
            'project_id' => $this->project_id,
            'name' => $this->name,
            'description' => $this->description,
            'event_name' => $this->event_name,
            'conditions' => $this->conditions,
            'actions' => $this->actions,
            'is_active' => $this->is_active
        ];
    }
}
