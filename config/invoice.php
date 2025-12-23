<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Invoice Settings
    |--------------------------------------------------------------------------
    |
    | All settings related to the invoice system
    |
    */

    'company' => [
        'name' => env('COMPANY_NAME', 'Your Company Name'),
        'address' => env('COMPANY_ADDRESS', ''),
        'phone' => env('COMPANY_PHONE', ''),
        'email' => env('COMPANY_EMAIL', ''),
        'website' => env('COMPANY_WEBSITE', ''),
        'logo' => env('COMPANY_LOGO', ''),
        'tax_number' => env('COMPANY_TAX_NUMBER', ''),
    ],

    'invoice' => [
        'prefix' => env('INVOICE_PREFIX', 'INV-'),
        'next_number' => env('INVOICE_NEXT_NUMBER', 1001),
        'currency' => env('DEFAULT_CURRENCY', 'USD'),
        'tax_rate' => env('DEFAULT_TAX_RATE', 0),
        'due_days' => env('DEFAULT_DUE_DAYS', 30),
        'terms' => env('DEFAULT_TERMS', ''),
        'footer' => env('DEFAULT_FOOTER', ''),
    ],

    'payment' => [
        'methods' => ['cash', 'bank_transfer', 'credit_card', 'paypal', 'other'],
        'terms' => [
            'due_on_receipt' => 'Due on Receipt',
            'net_15' => 'Net 15 Days',
            'net_30' => 'Net 30 Days',
            'net_60' => 'Net 60 Days',
        ],
    ],

    'file' => [
        'max_size' => env('MAX_FILE_SIZE', 10240), // 10MB in KB
        'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'],
        'upload_path' => env('FILE_UPLOAD_PATH', 'uploads'),
    ],

    'pagination' => [
        'default' => env('PAGINATION_DEFAULT', 15),
        'max' => env('PAGINATION_MAX', 100),
    ],
];
