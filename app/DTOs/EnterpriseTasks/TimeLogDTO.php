<?php

namespace App\DTOs\EnterpriseTasks;

use Illuminate\Http\Request;

class TimeLogDTO
{
    public int $duration_minutes;
    public ?string $memo;
    public string $log_date;

    public function __construct(array $data)
    {
        $this->duration_minutes = (int)$data['duration_minutes'];
        $this->memo = $data['memo'] ?? null;
        $this->log_date = $data['log_date'] ?? date('Y-m-d');
    }

    public static function fromRequest(Request $request): self
    {
        return new self($request->all());
    }

    public function toArray(): array
    {
        return [
            'duration_minutes' => $this->duration_minutes,
            'memo' => $this->memo,
            'log_date' => $this->log_date
        ];
    }
}
