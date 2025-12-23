<?php

namespace App\Http\Requests\Admin\Invoice;

use App\Traits\ResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreInvoiceRequest extends FormRequest
{
    use ResponseTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.item_type' => 'nullable|in:service,product,hours,days,units',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'currency' => 'nullable|in:USD,EUR,GBP,SAR,AED',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'footer' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'client_id.required' => 'Client is required',
            'client_id.exists' => 'Selected client does not exist',
            'invoice_date.required' => 'Invoice date is required',
            'due_date.required' => 'Due date is required',
            'due_date.after_or_equal' => 'Due date must be on or after invoice date',
            'items.required' => 'At least one item is required',
            'items.*.description.required' => 'Item description is required',
            'items.*.quantity.required' => 'Item quantity is required',
            'items.*.quantity.min' => 'Item quantity must be at least 0.01',
            'items.*.unit_price.required' => 'Item unit price is required',
            'items.*.unit_price.min' => 'Item unit price must be at least 0',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->failureResponse($validator->errors()->first(), $validator->errors(), 422)
        );
    }
}
