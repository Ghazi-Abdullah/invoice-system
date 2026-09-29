<?php

namespace App\Http\Requests\Admin\Unit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unitId = $this->route('id');

        return [
            'name'         => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('units')->where(fn ($q) => $q->where('floor_id', $this->floor_id))->ignore($unitId),
            ],
            'area'         => ['nullable', 'numeric', 'min:0'],
            'monthly_rent' => ['nullable', 'numeric', 'min:0'],
            'description'  => ['nullable', 'string'],
            'is_active'    => ['boolean'],
        ];
    }
}
