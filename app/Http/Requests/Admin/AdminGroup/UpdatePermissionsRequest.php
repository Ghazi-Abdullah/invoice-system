<?php

namespace App\Http\Requests\Admin\AdminGroup;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'permissions' => 'required|array',
            'permissions.*' => 'integer|exists:admin_permissions,id'
        ];
    }

    public function messages()
    {
        return [
            'permissions.required' => 'يجب إدخال الصلاحيات',
            'permissions.array' => 'يجب أن تكون الصلاحيات مصفوفة',
            'permissions.*.integer' => 'يجب أن يكون كل صلاحية رقم',
            'permissions.*.exists' => 'بعض الصلاحيات غير موجودة في قاعدة البيانات'
        ];
    }
}
