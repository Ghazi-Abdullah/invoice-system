<?php
// TrackSupportTicketRequest.php — endpoint عام للزوار
namespace App\Http\Requests\Admin\SupportTicket;

use App\Traits\ResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TrackSupportTicketRequest extends FormRequest
{
    use ResponseTrait;

    public function authorize() { return true; }

    public function rules()
    {
        return [
            'ticket_number' => 'required|string|max:50',
            'email'         => 'required|email|max:255',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->failureResponse($validator->errors()->first(), $validator->errors(), 422)
        );
    }
}
