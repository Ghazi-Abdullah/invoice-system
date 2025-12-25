<?php

namespace App\Http\Requests\Admin\AdminGroup;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminGroupRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $groupId = $this->route('id');

        return [
            'title_en' => 'required|string|max:255|unique:admin_groups,title_en,' . $groupId,
            'title_ar' => 'required|string|max:255|unique:admin_groups,title_ar,' . $groupId,
            'description' => 'nullable|string|max:500',
            'is_active' => 'required|boolean',
        ];
    }

    // ⬇️ تم حذف دالة messages() بالكامل
    // ⬇️ تم حذف دالة attributes() بالكامل
}
