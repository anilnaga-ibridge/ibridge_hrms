<?php

namespace App\DTOs\EnterpriseTasks;

use Illuminate\Http\Request;
use App\Classes\Common;

class DependencyDTO
{
    public ?int $project_id;
    public int $task_id;
    public int $depends_on_task_id;
    public string $dependency_type;
    public int $lag_days;

    public function __construct(array $data)
    {
        $this->project_id = isset($data['x_project_id']) && $data['x_project_id'] ? Common::getIdFromHash($data['x_project_id']) : null;
        $this->task_id = Common::getIdFromHash($data['x_task_id']);
        $this->depends_on_task_id = Common::getIdFromHash($data['x_depends_on_task_id']);
        $this->dependency_type = $data['dependency_type'] ?? 'finish_to_start';
        $this->lag_days = (int)($data['lag_days'] ?? 0);
    }

    public static function fromRequest(Request $request): self
    {
        return new self($request->all());
    }

    public function toArray(): array
    {
        return [
            'project_id' => $this->project_id,
            'task_id' => $this->task_id,
            'depends_on_task_id' => $this->depends_on_task_id,
            'dependency_type' => $this->dependency_type,
            'lag_days' => $this->lag_days
        ];
    }
}
