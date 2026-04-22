<?php

namespace App\Http\Requests\Admin\Auth;

use App\Traits\ResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendOtpRequest extends FormRequest
{
    use ResponseTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ✅ أزلنا exists:users,email — لا نكشف إذا الإيميل مسجل أو لا
            'email' => 'required|email|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email'    => 'البريد الإلكتروني غير صحيح',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->failureResponse($validator->errors()->first(), $validator->errors(), 422)
        );
    }
}
