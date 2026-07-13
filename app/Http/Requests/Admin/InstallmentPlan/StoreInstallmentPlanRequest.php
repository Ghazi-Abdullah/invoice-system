<?php

namespace App\Http\Requests\Admin\InstallmentPlan;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstallmentPlanRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'number_of_installments' => 'required|integer|min:2|max:60',
            'interest_rate'          => 'nullable|numeric|min:0|max:100',
            'start_date'             => 'required|date|after_or_equal:today',
            'frequency'              => 'nullable|in:weekly,monthly',
            'notes'                  => 'nullable|string|max:1000',
        ];
    }
}
