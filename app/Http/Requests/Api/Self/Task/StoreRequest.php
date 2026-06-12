<?php

namespace App\Http\Requests\Api\Self\Task;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
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
