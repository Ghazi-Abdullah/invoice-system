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

    // ⬇️ تم حذف دالة messages() بالكامل
    // ⬇️ تم حذف دالة attributes() بالكامل
}
