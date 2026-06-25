<?php

namespace App\Http\Requests\Api\LeaveType;

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
        $rules = [
            'name' => 'required',
            'is_paid' => 'required',
            'total_leaves' => 'required',
            'max_leaves_per_month' => 'nullable|integer|min:0',
            'is_monthly_leave' => 'nullable|boolean',
            'monthly_leave_expiry_cycle' => 'nullable|integer|min:1',
        ];

        return $rules;
    }
}
