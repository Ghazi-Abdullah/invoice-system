<?php

namespace App\Http\Requests\Admin\Branch;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'name_en'   => ['nullable', 'string', 'max:255'],
            'code'      => ['required', 'string', 'max:50', 'unique:branches,code'],
            'address'   => ['nullable', 'string', 'max:1000'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'email'     => ['nullable', 'email', 'max:255'],
            'city'      => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],app/Http/Requests/Admin/Branch/UpdateBranchRequest.php
            'is_main'   => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'      => 'اسم الفرع',
            'name_en'   => 'اسم الفرع بالإنجليزية',
            'code'      => 'كود الفرع',
            'address'   => 'العنوان',
            'phone'     => 'الهاتف',
            'email'     => 'البريد الإلكتروني',
            'city'      => 'المدينة',
            'is_active' => 'الحالة',
            'is_main'   => 'الفرع الرئيسي',
        ];
    }
}
