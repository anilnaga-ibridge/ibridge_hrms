<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
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
			'email' => 'required|email',
			'phone' => 'integer',
			'dob' => 'nullable|date_format:Y-m-d',
			'personal_email' => 'nullable|email',
			'personal_phone' => 'nullable',
			'is_married' => 'nullable|integer',
		];

		if ($this->has('password') && $this->password != '') {
			$rules['password'] = 'min:8';
		}

		return $rules;
	}
}
