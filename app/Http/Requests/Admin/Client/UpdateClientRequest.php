<?php

namespace App\Http\Requests\Admin\Client;

use App\Traits\ResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateClientRequest extends FormRequest
{
    use ResponseTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $clientId = $this->route('id');

        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:clients,email,' . $clientId,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'company_name' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:50',
            'currency' => 'nullable|in:USD,EUR,GBP,SAR,AED',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->failureResponse($validator->errors()->first(), $validator->errors(), 422)
        );
    }
}
