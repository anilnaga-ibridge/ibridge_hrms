<?php

namespace App\DTOs\EnterpriseTasks;

use Illuminate\Http\Request;

class SavedViewDTO
{
    public string $name;
    public ?string $icon;
    public ?array $filters;
    public ?string $grouping;
    public ?string $sorting;
    public bool $is_default;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->icon = $data['icon'] ?? null;
        $this->filters = $data['filters'] ?? null;
        $this->grouping = $data['grouping'] ?? null;
        $this->sorting = $data['sorting'] ?? null;
        $this->is_default = $data['is_default'] ?? false;
    }

    public static function fromRequest(Request $request): self
    {
        return new self($request->all());
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'icon' => $this->icon,
            'filters' => $this->filters,
            'grouping' => $this->grouping,
            'sorting' => $this->sorting,
            'is_default' => $this->is_default
        ];
    }
}
