<?php

namespace App\Http\Requests\Admin\User;

use App\Traits\ResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChangePasswordRequest extends FormRequest
{
    use ResponseTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed|different:current_password',
        ];
    }

    public function messages()
    {
        return [
            'current_password.required' => 'Current password is required',
            'new_password.required' => 'New password is required',
            'new_password.min' => 'New password must be at least 8 characters',
            'new_password.confirmed' => 'Password confirmation does not match',
            'new_password.different' => 'New password must be different from current password',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->failureResponse($validator->errors()->first(), $validator->errors(), 422)
        );
    }
}
