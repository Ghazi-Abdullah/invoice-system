<?php

namespace App\Http\Requests\Admin\SupportTicket;

use App\Traits\ResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreSupportTicketRequest extends FormRequest
{
    use ResponseTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'subject'  => 'required|string|max:255',
            'message'  => 'required|string|max:1000',
            'priority' => 'nullable|in:low,medium,high',
            // ✅ honeypot — يجب أن يبقى فارغًا دائمًا من مستخدم حقيقي
            'website'  => 'prohibited',

        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->failureResponse($validator->errors()->first(), $validator->errors(), 422)
        );
    }
}
