<?php

namespace App\DTOs\EnterpriseTasks;

use Illuminate\Http\Request;

class TaskTemplateDTO
{
    public string $name;
    public ?string $description;
    public ?string $category;
    public bool $is_global;
    public array $items = [];

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->description = $data['description'] ?? null;
        $this->category = $data['category'] ?? null;
        $this->is_global = $data['is_global'] ?? false;
        
        if (isset($data['items']) && is_array($data['items'])) {
            $this->items = $data['items'];
        }
    }

    public static function fromRequest(Request $request): self
    {
        return new self($request->all());
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'is_global' => $this->is_global
        ];
    }
}
