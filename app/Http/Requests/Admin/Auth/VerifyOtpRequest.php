<?php

namespace App\Http\Requests\Admin\Auth;

use App\Traits\ResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerifyOtpRequest extends FormRequest
{
    use ResponseTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'otp'     => 'required|digits:6',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'معرف المستخدم مطلوب',
            'user_id.exists'   => 'المستخدم غير موجود',
            'otp.required'     => 'رمز التحقق مطلوب',
            'otp.digits'       => 'رمز التحقق يجب أن يكون 6 أرقام',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->failureResponse($validator->errors()->first(), $validator->errors(), 422)
        );
    }
}
