<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Constants\Constants;

class ValidCurrency implements Rule
{
    public function passes($attribute, $value)
    {
        $validCurrencies = [
            Constants::CURRENCY_USD,
            Constants::CURRENCY_EUR,
            Constants::CURRENCY_GBP,
            Constants::CURRENCY_SAR,
            Constants::CURRENCY_AED
        ];

        return in_array($value, $validCurrencies);
    }

    public function message()
    {
        return 'The selected currency is not valid.';
    }
}
