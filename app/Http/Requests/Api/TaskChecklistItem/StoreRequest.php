<?php

namespace App\Http\Requests\Api\TaskChecklistItem;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'task_id' => 'required',
            'name' => 'required',
        ];
    }
}
