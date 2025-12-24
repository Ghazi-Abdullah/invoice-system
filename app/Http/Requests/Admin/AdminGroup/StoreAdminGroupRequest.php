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
            'title_ar' => 'required|string|max:255|unique:admin_groups,title_ar',
            'description' => 'nullable|string|max:500',
            'is_active' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'title_en.required' => 'اسم المجموعة بالإنجليزية مطلوب.',
            'title_en.unique' => 'هذا الاسم بالإنجليزية موجود مسبقاً.',
            'title_en.max' => 'اسم المجموعة بالإنجليزية يجب ألا يتجاوز 255 حرفاً.',
            'title_ar.required' => 'اسم المجموعة بالعربية مطلوب.',
            'title_ar.unique' => 'هذا الاسم بالعربية موجود مسبقاً.',
            'title_ar.max' => 'اسم المجموعة بالعربية يجب ألا يتجاوز 255 حرفاً.',
            'description.max' => 'الوصف يجب ألا يتجاوز 500 حرف.',
            'is_active.required' => 'حالة المجموعة مطلوبة.',
            'is_active.boolean' => 'حالة المجموعة يجب أن تكون قيمة منطقية.',
        ];
    }
}
