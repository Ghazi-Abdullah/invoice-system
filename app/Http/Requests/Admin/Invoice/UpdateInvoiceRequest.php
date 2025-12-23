<?php

namespace App\Http\Requests\Admin\Invoice;

use App\Traits\ResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateInvoiceRequest extends FormRequest
{
    use ResponseTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'client_id' => 'sometimes|exists:clients,id',
            'invoice_date' => 'sometimes|date',
            'due_date' => 'sometimes|date|after_or_equal:invoice_date',
            'items' => 'sometimes|array|min:1',
            'items.*.description' => 'required_with:items|string|max:255',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.item_type' => 'nullable|in:service,product,hours,days,units',
            'subtotal' => 'sometimes|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total' => 'sometimes|numeric|min:0',
            'currency' => 'nullable|in:USD,EUR,GBP,SAR,AED',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'footer' => 'nullable|string',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->failureResponse($validator->errors()->first(), $validator->errors(), 422)
        );
    }
}
