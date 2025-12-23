<?php

namespace App\Http\Requests\Admin\AdminGroup;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminGroupRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title_en' => 'required|string|max:255|unique:admin_groups,title_en',
            'title_ar' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:admin_permissions,id'
        ];
    }

    public function messages()
    {
        return [
            'title_en.required' => 'العنوان الإنجليزي مطلوب',
            'title_en.unique' => 'هذا العنوان الإنجليزي مستخدم بالفعل',
            'title_ar.required' => 'العنوان العربي مطلوب',
            'permissions.*.integer' => 'يجب أن يكون كل صلاحية رقم',
            'permissions.*.exists' => 'بعض الصلاحيات غير موجودة في قاعدة البيانات'
        ];
    }
}
