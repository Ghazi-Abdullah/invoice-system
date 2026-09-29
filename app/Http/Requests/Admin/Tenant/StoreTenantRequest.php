<?php

namespace App\Http\Requests\Admin\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unit_id'   => ['nullable', 'exists:units,id'],
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['nullable', 'email', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'id_number' => ['nullable', 'string', 'max:50'],
            'notes'     => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
