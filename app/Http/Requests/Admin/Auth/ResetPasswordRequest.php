<?php

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'                 => ['required', 'string', 'email:rfc,dns', 'max:255'],
            'token'                 => ['required', 'string', 'min:32', 'max:128'],
            'password'              => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'     => 'البريد الإلكتروني مطلوب.',
            'token.required'       => 'رمز التحقق مطلوب.',
            'password.required'    => 'كلمة المرور الجديدة مطلوبة.',
            'password.confirmed'   => 'كلمة المرور وتأكيدها غير متطابقين.',
            'password.min'         => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->email)),
            ]);
        }
    }
}
