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
            'otp'     => 'required|string|size:6',
            // ✅ string بدل digits — لأن OTP بعد الـ hash مش أرقام فقط
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'معرف المستخدم مطلوب',
            'user_id.exists'   => 'المستخدم غير موجود',
            'otp.required'     => 'رمز التحقق مطلوب',
            'otp.size'         => 'رمز التحقق يجب أن يكون 6 خانات',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->failureResponse($validator->errors()->first(), $validator->errors(), 422)
        );
    }
}
