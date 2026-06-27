<?php

namespace App\DTOs\EnterpriseTasks;

use Illuminate\Http\Request;
use App\Classes\Common;

class TaskDTO
{
    public string $title;
    public ?string $description;
    public ?string $rich_text_description;
    public ?int $project_id;
    public ?int $section_id;
    public ?int $parent_id;
    public string $status;
    public string $priority;
    public ?string $due_date;
    public ?string $due_time;
    public ?string $start_date;
    public ?string $start_time;
    public ?string $deadline;
    public ?float $estimated_hours;
    public ?float $actual_hours;
    public string $recurrence_type;
    public ?string $recurrence_pattern;
    public array $assignees_ids = [];
    public array $reviewers_ids = [];
    public array $watchers_ids = [];
    public array $labels_ids = [];

    public function __construct(array $data)
    {
        $this->title = $data['title'];
        $this->description = $data['description'] ?? null;
        $this->rich_text_description = $data['rich_text_description'] ?? null;
        
        $this->project_id = isset($data['x_project_id']) && $data['x_project_id'] ? Common::getIdFromHash($data['x_project_id']) : null;
        $this->section_id = isset($data['x_section_id']) && $data['x_section_id'] ? Common::getIdFromHash($data['x_section_id']) : null;
        $this->parent_id = isset($data['x_parent_id']) && $data['x_parent_id'] ? Common::getIdFromHash($data['x_parent_id']) : null;

        $this->status = $data['status'] ?? 'pending';
        $this->priority = $data['priority'] ?? 'P3';
        
        $this->due_date = $data['due_date'] ?? null;
        $this->due_time = $data['due_time'] ?? null;
        $this->start_date = $data['start_date'] ?? null;
        $this->start_time = $data['start_time'] ?? null;
        $this->deadline = $data['deadline'] ?? null;
        
        $this->estimated_hours = isset($data['estimated_hours']) ? (float)$data['estimated_hours'] : null;
        $this->actual_hours = isset($data['actual_hours']) ? (float)$data['actual_hours'] : null;

        $this->recurrence_type = $data['recurrence_type'] ?? 'none';
        $this->recurrence_pattern = $data['recurrence_pattern'] ?? null;

        if (isset($data['assignees_xids']) && is_array($data['assignees_xids'])) {
            $this->assignees_ids = array_filter(array_map([Common::class, 'getIdFromHash'], $data['assignees_xids']));
        }
        if (isset($data['reviewers_xids']) && is_array($data['reviewers_xids'])) {
            $this->reviewers_ids = array_filter(array_map([Common::class, 'getIdFromHash'], $data['reviewers_xids']));
        }
        if (isset($data['watchers_xids']) && is_array($data['watchers_xids'])) {
            $this->watchers_ids = array_filter(array_map([Common::class, 'getIdFromHash'], $data['watchers_xids']));
        }
        if (isset($data['labels_xids']) && is_array($data['labels_xids'])) {
            $this->labels_ids = array_filter(array_map([Common::class, 'getIdFromHash'], $data['labels_xids']));
        }
    }

    public static function fromRequest(Request $request): self
    {
        return new self($request->all());
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'rich_text_description' => $this->rich_text_description,
            'project_id' => $this->project_id,
            'section_id' => $this->section_id,
            'parent_id' => $this->parent_id,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date,
            'due_time' => $this->due_time,
            'start_date' => $this->start_date,
            'start_time' => $this->start_time,
            'deadline' => $this->deadline,
            'estimated_hours' => $this->estimated_hours,
            'actual_hours' => $this->actual_hours,
            'recurrence_type' => $this->recurrence_type,
            'recurrence_pattern' => $this->recurrence_pattern
        ];
    }
}
