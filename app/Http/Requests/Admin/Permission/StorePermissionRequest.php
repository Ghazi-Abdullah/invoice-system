<?php

namespace App\Http\Requests\Admin\Permission;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255|unique:admin_permissions,title',
            'description_en' => 'nullable|string|max:500',
            'description_ar' => 'nullable|string|max:500',
            'admin_menu_id' => 'nullable|integer|exists:admin_menus,id',
            'admin_sub_menu_id' => 'nullable|integer|exists:admin_sub_menus,id',
            'parent_id' => 'nullable|integer|exists:admin_permissions,id',
            'is_parent' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'The permission title is required.',
            'title.unique' => 'This permission title already exists.',
            'admin_menu_id.exists' => 'The selected menu does not exist.',
            'admin_sub_menu_id.exists' => 'The selected sub menu does not exist.',
            'parent_id.exists' => 'The selected parent permission does not exist.',
        ];
    }
}
