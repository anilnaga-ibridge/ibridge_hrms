<?php

namespace App\Http\Requests\Api\Customer;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
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
