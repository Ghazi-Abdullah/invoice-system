<?php

return [
    'accepted' => 'يجب قبول :attribute',
    'accepted_if' => 'يجب قبول :attribute عندما يكون :other :value.',
    'active_url' => ':attribute ليس عنوان URL صالحًا',
    'after' => 'يجب أن يكون :attribute تاريخًا بعد :date.',
    'after_or_equal' => 'يجب أن يكون :attribute تاريخًا بعد أو يساوي :date.',
    'alpha' => 'يجب أن يحتوي :attribute على أحرف فقط.',
    'alpha_dash' => 'يجب أن يحتوي :attribute على أحرف وأرقام وشرطات وشرطات سفلية فقط.',
    'alpha_num' => 'يجب أن يحتوي :attribute على أحرف وأرقام فقط.',
    'array' => 'يجب أن يكون :attribute مصفوفة.',
    'before' => 'يجب أن يكون :attribute تاريخًا قبل :date.',
    'before_or_equal' => 'يجب أن يكون :attribute تاريخًا قبل أو يساوي :date.',
    'between' => [
        'array' => 'يجب أن يحتوي :attribute على :min - :max عنصر.',
        'file' => 'يجب أن يكون :attribute بين :min و :max كيلوبايت.',
        'numeric' => 'يجب أن يكون :attribute بين :min و :max.',
        'string' => 'يجب أن يكون :attribute بين :min و :max حرفًا.',
    ],
    'boolean' => 'يجب أن يكون حقل :attribute صحيحًا أو خاطئًا.',
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => ':attribute ليس تاريخًا صالحًا.',
    'date_equals' => 'يجب أن يكون :attribute تاريخًا يساوي :date.',
    'date_format' => 'لا يتطابق :attribute مع الصيغة :format.',
    'declined' => 'يجب رفض :attribute.',
    'declined_if' => 'يجب رفض :attribute عندما يكون :other :value.',
    'different' => 'يجب أن يكون :attribute و :other مختلفين.',
    'digits' => 'يجب أن يكون :attribute :digits أرقام.',
    'digits_between' => 'يجب أن يكون :attribute بين :min و :max أرقام.',
    'dimensions' => ':attribute أبعاد الصورة غير صالحة.',
    'distinct' => 'حقل :attribute يحتوي على قيمة مكررة.',
    'doesnt_start_with' => 'لا يجب أن يبدأ :attribute بأي مما يلي: :values.',
    'email' => 'يجب أن يكون :attribute عنوان بريد إلكتروني صالح.',
    'ends_with' => 'يجب أن ينتهي :attribute بأحد القيم التالية: :values.',
    'enum' => ':attribute المحدد غير صالح.',
    'exists' => ':attribute المحدد غير صالح.',
    'file' => 'يجب أن يكون :attribute ملفًا.',
    'filled' => 'حقل :attribute مطلوب.',
    'gt' => [
        'array' => 'يجب أن يحتوي :attribute على أكثر من :value عنصر.',
        'file' => 'يجب أن يكون :attribute أكبر من :value كيلوبايت.',
        'numeric' => 'يجب أن يكون :attribute أكبر من :value.',
        'string' => 'يجب أن يكون :attribute أكبر من :value حرفًا.',
    ],
    'gte' => [
        'array' => 'يجب أن يحتوي :attribute على :value عناصر أو أكثر.',
        'file' => 'يجب أن يكون :attribute أكبر من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن يكون :attribute أكبر من أو يساوي :value.',
        'string' => 'يجب أن يكون :attribute أكبر من أو يساوي :value حرفًا.',
    ],
    'image' => 'يجب أن يكون :attribute صورة.',
    'in' => ':attribute المحدد غير صالح.',
    'in_array' => 'حقل :attribute غير موجود في :other.',
    'integer' => 'يجب أن يكون :attribute عددًا صحيحًا.',
    'ip' => 'يجب أن يكون :attribute عنوان IP صالحًا.',
    'ipv4' => 'يجب أن يكون :attribute عنوان IPv4 صالحًا.',
    'ipv6' => 'يجب أن يكون :attribute عنوان IPv6 صالحًا.',
    'json' => 'يجب أن يكون :attribute نصًا بصيغة JSON.',
    'lt' => [
        'array' => 'يجب أن يحتوي :attribute على أقل من :value عناصر.',
        'file' => 'يجب أن يكون :attribute أقل من :value كيلوبايت.',
        'numeric' => 'يجب أن يكون :attribute أقل من :value.',
        'string' => 'يجب أن يكون :attribute أقل من :value حرفًا.',
    ],
    'lte' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :value عناصر.',
        'file' => 'يجب أن يكون :attribute أقل من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن يكون :attribute أقل من أو يساوي :value.',
        'string' => 'يجب أن يكون :attribute أقل من أو يساوي :value حرفًا.',
    ],
    'mac_address' => 'يجب أن يكون :attribute عنوان MAC صالحًا.',
    'max' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عناصر.',
        'file' => 'يجب ألا يزيد :attribute عن :max كيلوبايت.',
        'numeric' => 'يجب ألا يزيد :attribute عن :max.',
        'string' => 'يجب ألا يزيد :attribute عن :max حرفًا.',
    ],
    'mimes' => 'يجب أن يكون :attribute ملفًا من نوع: :values.',
    'mimetypes' => 'يجب أن يكون :attribute ملفًا من نوع: :values.',
    'min' => [
        'array' => 'يجب أن يحتوي :attribute على الأقل :min عناصر.',
        'file' => 'يجب أن يكون :attribute على الأقل :min كيلوبايت.',
        'numeric' => 'يجب أن يكون :attribute على الأقل :min.',
        'string' => 'يجب أن يكون :attribute على الأقل :min حرفًا.',
    ],
    'multiple_of' => 'يجب أن يكون :attribute من مضاعفات :value.',
    'not_in' => ':attribute المحدد غير صالح.',
    'not_regex' => 'صيغة :attribute غير صالحة.',
    'numeric' => 'يجب أن يكون :attribute رقمًا.',
    'password' => [
        'letters' => 'يجب أن تحتوي كلمة المرور على حرف واحد على الأقل.',
        'mixed' => 'يجب أن تحتوي كلمة المرور على حرف كبير وصغير على الأقل.',
        'numbers' => 'يجب أن تحتوي كلمة المرور على رقم واحد على الأقل.',
        'symbols' => 'يجب أن تحتوي كلمة المرور على رمز واحد على الأقل.',
        'uncompromised' => 'ظهرت كلمة المرور :attribute في تسريب للبيانات. يرجى اختيار كلمة مرور مختلفة.',
    ],
    'present' => 'يجب تقديم حقل :attribute.',
    'prohibited' => 'حقل :attribute محظور.',
    'prohibited_if' => 'حقل :attribute محظور عندما يكون :other :value.',
    'prohibited_unless' => 'حقل :attribute محظور ما لم يكن :other في :values.',
    'prohibits' => 'حقل :attribute يحظر وجود :other.',
    'regex' => 'صيغة :attribute غير صالحة.',
    'required' => 'حقل :attribute مطلوب.',
    'required_array_keys' => 'يجب أن يحتوي حقل :attribute على مدخلات لـ: :values.',
    'required_if' => 'حقل :attribute مطلوب عندما يكون :other :value.',
    'required_unless' => 'حقل :attribute مطلوب ما لم يكن :other في :values.',
    'required_with' => 'حقل :attribute مطلوب عندما تكون :values موجودة.',
    'required_with_all' => 'حقل :attribute مطلوب عندما تكون :values موجودة.',
    'required_without' => 'حقل :attribute مطلوب عندما لا تكون :values موجودة.',
    'required_without_all' => 'حقل :attribute مطلوب عندما لا تكون أي من :values موجودة.',
    'same' => 'يجب أن يتطابق :attribute مع :other.',
    'size' => [
        'array' => 'يجب أن يحتوي :attribute على :size عناصر.',
        'file' => 'يجب أن يكون :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن يكون :attribute :size.',
        'string' => 'يجب أن يكون :attribute :size حرفًا.',
    ],
    'starts_with' => 'يجب أن يبدأ :attribute بأحد القيم التالية: :values.',
    'string' => 'يجب أن يكون :attribute نصًا.',
    'timezone' => 'يجب أن يكون :attribute منطقة زمنية صالحة.',
    'unique' => 'قيمة :attribute مستخدمة بالفعل.',
    'uploaded' => 'فشل تحميل :attribute.',
    'url' => 'يجب أن يكون :attribute عنوان URL صالحًا.',
    'uuid' => 'يجب أن يكون :attribute معرف UUID صالحًا.',

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
            'required' => 'العنوان بالإنجليزية مطلوب',
            'unique' => 'هذا العنوان بالإنجليزية مستخدم بالفعل',
        ],
        'title_ar' => [
            'required' => 'العنوان بالعربية مطلوب',
            'unique' => 'هذا العنوان بالعربية مستخدم بالفعل',
        ],
        'description' => [
            'max' => 'الوصف يجب ألا يتجاوز :max حرفًا',
        ],
        'is_active' => [
            'required' => 'حالة المجموعة مطلوبة',
            'boolean' => 'حالة المجموعة يجب أن تكون نعم أو لا',
        ],
        'permissions' => [
            'required' => 'الصلاحيات مطلوبة',
            'array' => 'يجب أن تكون الصلاحيات مصفوفة',
        ],
        'permissions.*' => [
            'exists' => 'الصلاحية المحددة غير موجودة',
        ],

        // Client
        'name' => [
            'required' => 'اسم العميل مطلوب',
        ],
        'email' => [
            'required' => 'البريد الإلكتروني مطلوب',
            'email' => 'يرجى إدخال بريد إلكتروني صحيح',
            'unique' => 'هذا البريد الإلكتروني مسجل بالفعل',
        ],
        'phone' => [
            'max' => 'رقم الهاتف يجب ألا يتجاوز :max حرفًا',
        ],
        'company_name' => [
            'max' => 'اسم الشركة يجب ألا يتجاوز :max حرفًا',
        ],
        'tax_number' => [
            'max' => 'الرقم الضريبي يجب ألا يتجاوز :max حرفًا',
        ],
        'payment_terms' => [
            'max' => 'شروط الدفع يجب ألا تتجاوز :max حرفًا',
        ],

        // Invoice
        'client_id' => [
            'required' => 'العميل مطلوب',
            'exists' => 'العميل المحدد غير موجود',
        ],
        'invoice_date' => [
            'required' => 'تاريخ الفاتورة مطلوب',
        ],
        'due_date' => [
            'required' => 'تاريخ الاستحقاق مطلوب',
            'after_or_equal' => 'تاريخ الاستحقاق يجب أن يكون في نفس تاريخ الفاتورة أو بعده',
        ],
        'items' => [
            'required' => 'على الأقل عنصر واحد مطلوب',
            'min' => 'يجب أن تحتوي الفاتورة على عنصر واحد على الأقل',
        ],
        'items.*.description' => [
            'required' => 'وصف العنصر مطلوب',
            'max' => 'وصف العنصر يجب ألا يتجاوز :max حرفًا',
        ],
        'items.*.quantity' => [
            'required' => 'كمية العنصر مطلوبة',
            'min' => 'كمية العنصر يجب أن تكون على الأقل :min',
        ],
        'items.*.unit_price' => [
            'required' => 'سعر وحدة العنصر مطلوب',
            'min' => 'سعر وحدة العنصر يجب أن يكون على الأقل :min',
        ],
        'items.*.tax_rate' => [
            'min' => 'معدل الضريبة يجب أن يكون على الأقل :min',
            'max' => 'معدل الضريبة يجب ألا يتجاوز :max',
        ],
        'items.*.item_type' => [
            'in' => 'نوع العنصر المحدد غير صالح',
        ],
        'subtotal' => [
            'required' => 'المجموع الفرعي مطلوب',
            'min' => 'المجموع الفرعي يجب أن يكون على الأقل :min',
        ],
        'tax_amount' => [
            'min' => 'مبلغ الضريبة يجب أن يكون على الأقل :min',
        ],
        'discount_amount' => [
            'min' => 'مبلغ الخصم يجب أن يكون على الأقل :min',
        ],
        'total' => [
            'required' => 'المجموع الكلي مطلوب',
            'min' => 'المجموع الكلي يجب أن يكون على الأقل :min',
        ],
        'currency' => [
            'in' => 'العملة المحددة غير صالحة',
        ],

        // Payment
        'payment_date' => [
            'required' => 'تاريخ الدفع مطلوب',
        ],
        'payment_method' => [
            'max' => 'طريقة الدفع يجب ألا تتجاوز :max حرفًا',
        ],
        'reference' => [
            'max' => 'المرجع يجب ألا يتجاوز :max حرفًا',
        ],

        // Send Invoice
        'message' => [
            'nullable' => 'الرسالة اختيارية',
        ],
        'send_copy' => [
            'boolean' => 'حقل إرسال نسخة يجب أن يكون نعم أو لا',
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
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',

        // Admin Group
        'title_en' => 'العنوان بالإنجليزية',
        'title_ar' => 'العنوان بالعربية',
        'description' => 'الوصف',
        'is_active' => 'حالة المجموعة',
        'permissions' => 'الصلاحيات',
        'permissions.*' => 'الصلاحية',

        // Client
        'phone' => 'الهاتف',
        'address' => 'العنوان',
        'company_name' => 'اسم الشركة',
        'tax_number' => 'الرقم الضريبي',
        'payment_terms' => 'شروط الدفع',
        'currency' => 'العملة',
        'notes' => 'ملاحظات',
        'is_active' => 'الحالة',

        // Invoice
        'client_id' => 'العميل',
        'invoice_date' => 'تاريخ الفاتورة',
        'due_date' => 'تاريخ الاستحقاق',
        'items' => 'العناصر',
        'items.*.description' => 'وصف العنصر',
        'items.*.quantity' => 'الكمية',
        'items.*.unit_price' => 'سعر الوحدة',
        'items.*.tax_rate' => 'معدل الضريبة',
        'items.*.item_type' => 'نوع العنصر',
        'subtotal' => 'المجموع الفرعي',
        'tax_amount' => 'مبلغ الضريبة',
        'discount_amount' => 'مبلغ الخصم',
        'total' => 'المجموع الكلي',
        'terms' => 'الشروط',
        'footer' => 'التذييل',

        // Payment (MarkAsPaid)
        'payment_date' => 'تاريخ الدفع',
        'payment_method' => 'طريقة الدفع',
        'reference' => 'المرجع',

        // Send Invoice
        'message' => 'الرسالة',
        'send_copy' => 'إرسال نسخة',
    ],
];
