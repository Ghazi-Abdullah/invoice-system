<?php

namespace App\Http\Requests\Admin\AdminGroup;

use Illuminate\Foundation\Http\FormRequest;

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
            'permissions.*' => 'exists:admin_permissions,id',
        ];
    }

    // ⬇️ تم حذف دالة messages() بالكامل
    // ⬇️ تم حذف دالة attributes() بالكامل
}
