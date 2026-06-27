<?php

namespace App\Http\Requests\Api\TaskComment;

use Illuminate\Foundation\Http\FormRequest;

class DeleteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [];
    }
}
