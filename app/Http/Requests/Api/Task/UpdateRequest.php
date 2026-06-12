<?php

namespace App\Http\Requests\Api\Task;

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
            'name'         => 'required',
            'project_id'   => 'nullable',
            'is_public'    => 'boolean',
            'is_billable'  => 'boolean',
            'hourly_rate'  => 'nullable|numeric',
            'start_date'   => 'required|date',
            'due_date'     => 'nullable|date',
            'assignees'    => 'nullable|array',
            'followers'    => 'nullable|array',
            'tags'         => 'nullable|array',
        ];
    }
}
