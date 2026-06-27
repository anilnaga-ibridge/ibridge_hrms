<?php

namespace App\DTOs\EnterpriseTasks;

use Illuminate\Http\Request;
use App\Classes\Common;

class ProjectDTO
{
    public string $name;
    public string $visibility;
    public string $status;
    public ?string $color;
    public ?string $description;
    public ?int $owner_id;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->visibility = $data['visibility'] ?? 'public';
        $this->status = $data['status'] ?? 'active';
        $this->color = $data['color'] ?? '#3b82f6';
        $this->description = $data['description'] ?? null;
        $this->owner_id = isset($data['x_owner_id']) ? Common::getIdFromHash($data['x_owner_id']) : auth('api')->id();
    }

    public static function fromRequest(Request $request): self
    {
        return new self($request->all());
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'visibility' => $this->visibility,
            'status' => $this->status,
            'color' => $this->color,
            'description' => $this->description,
            'owner_id' => $this->owner_id
        ];
    }
}
