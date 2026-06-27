<?php

namespace App\Http\Requests\Api\TaskComment;

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
            'comment' => 'required',
        ];
    }
}
