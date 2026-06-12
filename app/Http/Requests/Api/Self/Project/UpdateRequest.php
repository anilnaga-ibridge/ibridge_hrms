<?php

namespace App\Http\Requests\Api\Self\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'               => 'required',
            'start_date'         => 'required|date',
            'deadline'           => 'nullable|date',
            'status'             => 'nullable|string',
            'customer'           => 'nullable|string',
            'calculate_progress' => 'boolean',
            'progress'           => 'nullable|integer',
            'billing_type'       => 'nullable|string',
            'total_rate'         => 'nullable|numeric',
            'estimated_hours'    => 'nullable|numeric',
            'members'            => 'nullable|array',
            'tags'               => 'nullable|array',
            'send_email'         => 'boolean',
        ];
    }
}
