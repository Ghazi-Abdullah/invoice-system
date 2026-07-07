<?php

namespace App\Http\Requests\Admin\InstallmentPlan;

use Illuminate\Foundation\Http\FormRequest;

class PayInstallmentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'payment_method' => 'nullable|string|in:cash,bank_transfer,card,cheque',
        ];
    }
}
