<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Constants\Constants;

class ValidInvoiceStatus implements Rule
{
    public function passes($attribute, $value)
    {
        $validStatuses = [
            Constants::INVOICE_STATUS_DRAFT,
            Constants::INVOICE_STATUS_SENT,
            Constants::INVOICE_STATUS_PAID,
            Constants::INVOICE_STATUS_OVERDUE,
            Constants::INVOICE_STATUS_CANCELLED
        ];

        return in_array($value, $validStatuses);
    }

    public function message()
    {
        return 'The selected invoice status is not valid.';
    }
}
