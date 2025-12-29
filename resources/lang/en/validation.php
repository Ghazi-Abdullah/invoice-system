<?php

return [
    'accepted' => 'The :attribute must be accepted.',
    'accepted_if' => 'The :attribute must be accepted when :other is :value.',
    'active_url' => 'The :attribute is not a valid URL.',
    'after' => 'The :attribute must be a date after :date.',
    'after_or_equal' => 'The :attribute must be a date after or equal to :date.',
    'alpha' => 'The :attribute must only contain letters.',
    'alpha_dash' => 'The :attribute must only contain letters, numbers, dashes and underscores.',
    'alpha_num' => 'The :attribute must only contain letters and numbers.',
    'array' => 'The :attribute must be an array.',
    'before' => 'The :attribute must be a date before :date.',
    'before_or_equal' => 'The :attribute must be a date before or equal to :date.',
    'between' => [
        'array' => 'The :attribute must have between :min and :max items.',
        'file' => 'The :attribute must be between :min and :max kilobytes.',
        'numeric' => 'The :attribute must be between :min and :max.',
        'string' => 'The :attribute must be between :min and :max characters.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'current_password' => 'The password is incorrect.',
    'date' => 'The :attribute is not a valid date.',
    'date_equals' => 'The :attribute must be a date equal to :date.',
    'date_format' => 'The :attribute does not match the format :format.',
    'declined' => 'The :attribute must be declined.',
    'declined_if' => 'The :attribute must be declined when :other is :value.',
    'different' => 'The :attribute and :other must be different.',
    'digits' => 'The :attribute must be :digits digits.',
    'digits_between' => 'The :attribute must be between :min and :max digits.',
    'dimensions' => 'The :attribute has invalid image dimensions.',
    'distinct' => 'The :attribute field has a duplicate value.',
    'doesnt_start_with' => 'The :attribute may not start with one of the following: :values.',
    'email' => 'The :attribute must be a valid email address.',
    'ends_with' => 'The :attribute must end with one of the following: :values.',
    'enum' => 'The selected :attribute is invalid.',
    'exists' => 'The selected :attribute is invalid.',
    'file' => 'The :attribute must be a file.',
    'filled' => 'The :attribute field must have a value.',
    'gt' => [
        'array' => 'The :attribute must have more than :value items.',
        'file' => 'The :attribute must be greater than :value kilobytes.',
        'numeric' => 'The :attribute must be greater than :value.',
        'string' => 'The :attribute must be greater than :value characters.',
    ],
    'gte' => [
        'array' => 'The :attribute must have :value items or more.',
        'file' => 'The :attribute must be greater than or equal to :value kilobytes.',
        'numeric' => 'The :attribute must be greater than or equal to :value.',
        'string' => 'The :attribute must be greater than or equal to :value characters.',
    ],
    'image' => 'The :attribute must be an image.',
    'in' => 'The selected :attribute is invalid.',
    'in_array' => 'The :attribute field does not exist in :other.',
    'integer' => 'The :attribute must be an integer.',
    'ip' => 'The :attribute must be a valid IP address.',
    'ipv4' => 'The :attribute must be a valid IPv4 address.',
    'ipv6' => 'The :attribute must be a valid IPv6 address.',
    'json' => 'The :attribute must be a valid JSON string.',
    'lt' => [
        'array' => 'The :attribute must have less than :value items.',
        'file' => 'The :attribute must be less than :value kilobytes.',
        'numeric' => 'The :attribute must be less than :value.',
        'string' => 'The :attribute must be less than :value characters.',
    ],
    'lte' => [
        'array' => 'The :attribute must not have more than :value items.',
        'file' => 'The :attribute must be less than or equal to :value kilobytes.',
        'numeric' => 'The :attribute must be less than or equal to :value.',
        'string' => 'The :attribute must be less than or equal to :value characters.',
    ],
    'mac_address' => 'The :attribute must be a valid MAC address.',
    'max' => [
        'array' => 'The :attribute must not have more than :max items.',
        'file' => 'The :attribute must not be greater than :max kilobytes.',
        'numeric' => 'The :attribute must not be greater than :max.',
        'string' => 'The :attribute must not be greater than :max characters.',
    ],
    'mimes' => 'The :attribute must be a file of type: :values.',
    'mimetypes' => 'The :attribute must be a file of type: :values.',
    'min' => [
        'array' => 'The :attribute must have at least :min items.',
        'file' => 'The :attribute must be at least :min kilobytes.',
        'numeric' => 'The :attribute must be at least :min.',
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'multiple_of' => 'The :attribute must be a multiple of :value.',
    'not_in' => 'The selected :attribute is invalid.',
    'not_regex' => 'The :attribute format is invalid.',
    'numeric' => 'The :attribute must be a number.',
    'password' => [
        'letters' => 'The :attribute must contain at least one letter.',
        'mixed' => 'The :attribute must contain at least one uppercase and one lowercase letter.',
        'numbers' => 'The :attribute must contain at least one number.',
        'symbols' => 'The :attribute must contain at least one symbol.',
        'uncompromised' => 'The given :attribute has appeared in a data leak. Please choose a different :attribute.',
    ],
    'present' => 'The :attribute field must be present.',
    'prohibited' => 'The :attribute field is prohibited.',
    'prohibited_if' => 'The :attribute field is prohibited when :other is :value.',
    'prohibited_unless' => 'The :attribute field is prohibited unless :other is in :values.',
    'prohibits' => 'The :attribute field prohibits :other from being present.',
    'regex' => 'The :attribute format is invalid.',
    'required' => 'The :attribute field is required.',
    'required_array_keys' => 'The :attribute field must contain entries for: :values.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_unless' => 'The :attribute field is required unless :other is in :values.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same' => 'The :attribute must match :other.',
    'size' => [
        'array' => 'The :attribute must contain :size items.',
        'file' => 'The :attribute must be :size kilobytes.',
        'numeric' => 'The :attribute must be :size.',
        'string' => 'The :attribute must be :size characters.',
    ],
    'starts_with' => 'The :attribute must start with one of the following: :values.',
    'string' => 'The :attribute must be a string.',
    'timezone' => 'The :attribute must be a valid timezone.',
    'unique' => 'The :attribute has already been taken.',
    'uploaded' => 'The :attribute failed to upload.',
    'url' => 'The :attribute must be a valid URL.',
    'uuid' => 'The :attribute must be a valid UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        // Admin Group
        'title_en' => [
            'required' => 'Title in English is required',
            'unique' => 'This title in English is already taken',
        ],
        'title_ar' => [
            'required' => 'Title in Arabic is required',
            'unique' => 'This title in Arabic is already taken',
        ],
        'description' => [
            'max' => 'Description may not be greater than :max characters',
        ],
        'is_active' => [
            'required' => 'Group status is required',
            'boolean' => 'Group status field must be true or false',
        ],
        'permissions' => [
            'required' => 'Permissions are required',
            'array' => 'Permissions must be an array',
        ],
        'permissions.*' => [
            'exists' => 'Selected permission does not exist',
        ],

        // Client
        'name' => [
            'required' => 'Client name is required',
        ],
        'email' => [
            'required' => 'Email is required',
            'email' => 'Please enter a valid email',
            'unique' => 'This email is already registered',
        ],
        'phone' => [
            'max' => 'Phone number may not be greater than :max characters',
        ],
        'company_name' => [
            'max' => 'Company name may not be greater than :max characters',
        ],
        'tax_number' => [
            'max' => 'Tax number may not be greater than :max characters',
        ],
        'payment_terms' => [
            'max' => 'Payment terms may not be greater than :max characters',
        ],

        // Invoice
        'client_id' => [
            'required' => 'Client is required',
            'exists' => 'Selected client does not exist',
        ],
        'invoice_date' => [
            'required' => 'Invoice date is required',
        ],
        'due_date' => [
            'required' => 'Due date is required',
            'after_or_equal' => 'Due date must be on or after invoice date',
        ],
        'items' => [
            'required' => 'At least one item is required',
            'min' => 'Invoice must have at least one item',
        ],
        'items.*.description' => [
            'required' => 'Item description is required',
            'max' => 'Item description may not be greater than :max characters',
        ],
        'items.*.quantity' => [
            'required' => 'Item quantity is required',
            'min' => 'Item quantity must be at least :min',
        ],
        'items.*.unit_price' => [
            'required' => 'Item unit price is required',
            'min' => 'Item unit price must be at least :min',
        ],
        'items.*.tax_rate' => [
            'min' => 'Tax rate must be at least :min',
            'max' => 'Tax rate may not be greater than :max',
        ],
        'items.*.item_type' => [
            'in' => 'Selected item type is invalid',
        ],
        'subtotal' => [
            'required' => 'Subtotal is required',
            'min' => 'Subtotal must be at least :min',
        ],
        'tax_amount' => [
            'min' => 'Tax amount must be at least :min',
        ],
        'discount_amount' => [
            'min' => 'Discount amount must be at least :min',
        ],
        'total' => [
            'required' => 'Total is required',
            'min' => 'Total must be at least :min',
        ],
        'currency' => [
            'in' => 'Selected currency is invalid',
        ],

        // Payment
        'payment_date' => [
            'required' => 'Payment date is required',
        ],
        'payment_method' => [
            'max' => 'Payment method may not be greater than :max characters',
        ],
        'reference' => [
            'max' => 'Reference may not be greater than :max characters',
        ],

        // Send Invoice
        'message' => [
            'nullable' => 'Message is optional',
        ],
        'send_copy' => [
            'boolean' => 'Send copy field must be true or false',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        // General
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'password_confirmation' => 'Password Confirmation',

        // Admin Group
        'title_en' => 'Title in English',
        'title_ar' => 'Title in Arabic',
        'description' => 'Description',
        'is_active' => 'Group Status',
        'permissions' => 'Permissions',
        'permissions.*' => 'Permission',

        // Client
        'phone' => 'Phone',
        'address' => 'Address',
        'company_name' => 'Company Name',
        'tax_number' => 'Tax Number',
        'payment_terms' => 'Payment Terms',
        'currency' => 'Currency',
        'notes' => 'Notes',
        'is_active' => 'Status',

        // Invoice
        'client_id' => 'Client',
        'invoice_date' => 'Invoice Date',
        'due_date' => 'Due Date',
        'items' => 'Items',
        'items.*.description' => 'Item Description',
        'items.*.quantity' => 'Quantity',
        'items.*.unit_price' => 'Unit Price',
        'items.*.tax_rate' => 'Tax Rate',
        'items.*.item_type' => 'Item Type',
        'subtotal' => 'Subtotal',
        'tax_amount' => 'Tax Amount',
        'discount_amount' => 'Discount Amount',
        'total' => 'Total',
        'terms' => 'Terms',
        'footer' => 'Footer',

        // Payment (MarkAsPaid)
        'payment_date' => 'Payment Date',
        'payment_method' => 'Payment Method',
        'reference' => 'Reference',

        // Send Invoice
        'message' => 'Message',
        'send_copy' => 'Send Copy',
    ],
];
