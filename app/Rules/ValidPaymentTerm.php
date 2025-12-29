<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Constants\Constants;

class ValidPaymentTerm implements Rule
{
    public function passes($attribute, $value)
    {
        $validTerms = [
            Constants::PAYMENT_TERM_DUE_ON_RECEIPT,
            Constants::PAYMENT_TERM_NET_15,
            Constants::PAYMENT_TERM_NET_30,
            Constants::PAYMENT_TERM_NET_60
        ];

        return in_array($value, $validTerms);
    }

    public function message()
    {
        return 'The selected payment term is not valid.';
    }
}
