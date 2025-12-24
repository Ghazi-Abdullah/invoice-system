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

    public function messages()
    {
        return [
            'permissions.required' => 'Permissions are required.',
            'permissions.array' => 'Permissions must be an array.',
            'permissions.*.exists' => 'One or more permissions do not exist.',
        ];
    }
}
