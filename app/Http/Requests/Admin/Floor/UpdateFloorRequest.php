<?php

namespace App\Http\Requests\Admin\Floor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFloorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $floorId = $this->route('id');

        return [
            'name'         => ['sometimes', 'required', 'string', 'max:255'],
            'name_en'      => ['nullable', 'string', 'max:255'],
            'floor_number' => [
                'sometimes',
                'required',
                'integer',
                Rule::unique('floors')->where(fn ($q) => $q->where('property_id', $this->property_id))->ignore($floorId),
            ],
            'description'  => ['nullable', 'string'],
            'is_active'    => ['boolean'],
        ];
    }
}
