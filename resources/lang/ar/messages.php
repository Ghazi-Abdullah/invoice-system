<?php

return [
    // General Messages
    'success' => 'تمت العملية بنجاح',
    'error' => 'حدث خطأ',
    'not_found' => 'الملف غير موجود',
    'no_permission' => 'ليس لديك صلاحية للقيام بهذا الإجراء',
    'validation_error' => 'فشل التحقق',
    'server_error' => 'خطأ في الخادم الداخلي',

    // Auth Messages
    'login_success' => 'تم تسجيل الدخول بنجاح',
    'login_failed' => 'بيانات الاعتماد غير صالحة',
    'logout_success' => 'تم تسجيل الخروج بنجاح',
    'token_refreshed' => 'تم تحديث الرمز بنجاح',
    'user_fetched' => 'تم استرجاع بيانات المستخدم بنجاح',
    'profile_fetched' => 'تم استرجاع الملف الشخصي بنجاح',
    'profile_updated' => 'تم تحديث الملف الشخصي بنجاح',
    'password_changed' => 'تم تغيير كلمة المرور بنجاح',
    'password_invalid' => 'كلمة المرور الحالية غير صحيحة',
    'inactive_account' => 'حسابك غير نشط',
    'not_admin' => 'هذا الحساب لا يمكنه الوصول إلى لوحة التحكم',

    // Invoice Messages
    'invoices_fetched' => 'تم استرجاع الفواتير بنجاح',
    'invoice_fetched' => 'تم استرجاع الفاتورة بنجاح',
    'invoice_created' => 'تم إنشاء الفاتورة بنجاح',
    'invoice_updated' => 'تم تحديث الفاتورة بنجاح',
    'invoice_deleted' => 'تم حذف الفاتورة بنجاح',
    'invoice_sent' => 'تم إرسال الفاتورة بنجاح',
    'invoice_marked_paid' => 'تم تعليم الفاتورة كمقبوضة بنجاح',
    'invoice_duplicated' => 'تم نسخ الفاتورة بنجاح',
    'pdf_generated' => 'تم إنشاء ملف PDF بنجاح',
    'pdf_downloaded' => 'تم تحميل ملف PDF بنجاح',
    'only_draft_sent' => 'يمكن إرسال الفواتير المسودة فقط',

    // Client Messages
    'clients_fetched' => 'تم استرجاع العملاء بنجاح',
    'client_fetched' => 'تم استرجاع العميل بنجاح',
    'client_created' => 'تم إنشاء العميل بنجاح',
    'client_updated' => 'تم تحديث العميل بنجاح',
    'client_deleted' => 'تم حذف العميل بنجاح',
    'client_stats_fetched' => 'تم استرجاع إحصائيات العميل بنجاح',
    'client_invoices_fetched' => 'تم استرجاع فواتير العميل بنجاح',

    // User Messages
    'users_fetched' => 'تم استرجاع المستخدمين بنجاح',
    'user_fetched' => 'تم استرجاع المستخدم بنجاح',
    'user_created' => 'تم إنشاء المستخدم بنجاح',
    'user_updated' => 'تم تحديث المستخدم بنجاح',
    'user_deleted' => 'تم حذف المستخدم بنجاح',
    'user_status_updated' => 'تم تحديث حالة المستخدم بنجاح',
    'staff_users_fetched' => 'تم استرجاع مستخدمي الموظفين بنجاح',
    'client_users_fetched' => 'تم استرجاع مستخدمي العملاء بنجاح',

    // Admin Group Messages
    'admin_groups_fetched' => 'تم استرجاع مجموعات الإدارة بنجاح',
    'admin_group_fetched' => 'تم استرجاع مجموعة الإدارة بنجاح',
    'admin_group_created' => 'تم إنشاء مجموعة الإدارة بنجاح',
    'admin_group_updated' => 'تم تحديث مجموعة الإدارة بنجاح',
    'admin_group_deleted' => 'تم حذف مجموعة الإدارة بنجاح',
    'permissions_fetched' => 'تم استرجاع الصلاحيات بنجاح',
    'permissions_updated' => 'تم تحديث الصلاحيات بنجاح',
    'available_permissions_fetched' => 'تم استرجاع الصلاحيات المتاحة بنجاح',
    'groups_with_permissions_fetched' => 'تم استرجاع المجموعات مع الصلاحيات بنجاح',
    'cannot_delete_system_group' => 'لا يمكن حذف المجموعات النظامية',
    'cannot_delete_group_with_users' => 'لا يمكن حذف مجموعة بها مستخدمين معينين',

    // Report Messages
    'invoice_report_fetched' => 'تم استرجاع تقرير الفواتير بنجاح',
    'client_report_fetched' => 'تم استرجاع تقرير العملاء بنجاح',
    'payment_report_fetched' => 'تم استرجاع تقرير المدفوعات بنجاح',
    'tax_report_fetched' => 'تم استرجاع تقرير الضرائب بنجاح',
    'report_exported' => 'تم تصدير التقرير بنجاح',
    'recent_activity_fetched' => 'تم استرجاع النشاط الأخير بنجاح',
    'top_clients_fetched' => 'تم استرجاع أفضل العملاء بنجاح',
    'monthly_revenue_fetched' => 'تم استرجاع الإيرادات الشهرية بنجاح',

    // Dashboard Messages
    'dashboard_stats_fetched' => 'تم استرجاع إحصائيات لوحة التحكم بنجاح',
    'recent_invoices_fetched' => 'تم استرجاع الفواتير الأخيرة بنجاح',
    'overdue_invoices_fetched' => 'تم استرجاع الفواتير المتأخرة بنجاح',

    // Permission Messages
    'permissions_fetched' => 'تم استرجاع الصلاحيات بنجاح',
    'menus_fetched' => 'تم استرجاع القوائم بنجاح',

    // Validation Messages
    'required' => 'حقل :attribute مطلوب',
    'email' => 'يجب أن يكون :attribute بريدًا إلكترونيًا صالحًا',
    'unique' => 'هذا :attribute مستخدم بالفعل',
    'exists' => 'الـ :attribute المحدد غير صالح',
    'min' => 'يجب أن يكون :attribute على الأقل :min حرفًا',
    'max' => 'يجب ألا يزيد :attribute عن :max حرفًا',
    'numeric' => 'يجب أن يكون :attribute رقمًا',
    'date' => 'يجب أن يكون :attribute تاريخًا صالحًا',
    'after_or_equal' => 'يجب أن يكون :attribute تاريخًا بعد أو يساوي :date',
    'confirmed' => 'تأكيد :attribute لا يتطابق',
    'array' => 'يجب أن يكون :attribute مصفوفة',
    'in' => 'الـ :attribute المحدد غير صالح',
    'boolean' => 'يجب أن يكون حقل :attribute صحيحًا أو خاطئًا',
    'string' => 'يجب أن يكون :attribute نصًا',
    'failed' => 'بيانات الاعتماد هذه غير متطابقة مع سجلاتنا.',
    'password' => 'كلمة المرور غير صحيحة.',
    'throttle' => 'محاولات تسجيل دخول كثيرة جدًا. يرجى المحاولة مرة أخرى بعد :seconds ثانية.',
    'login_success' => 'تم تسجيل الدخول بنجاح',
    'login_failed' => 'بيانات الاعتماد غير صالحة',
    'logout_success' => 'تم تسجيل الخروج بنجاح',
    'token_refreshed' => 'تم تحديث الرمز بنجاح',
    'user_fetched' => 'تم استرجاع بيانات المستخدم بنجاح',
    'profile_fetched' => 'تم استرجاع الملف الشخصي بنجاح',
    'profile_updated' => 'تم تحديث الملف الشخصي بنجاح',
    'password_changed' => 'تم تغيير كلمة المرور بنجاح',
    'password_invalid' => 'كلمة المرور الحالية غير صحيحة',
    'inactive_account' => 'حسابك غير نشط',
    'not_admin' => 'هذا الحساب لا يمكنه الوصول إلى لوحة التحكم',
    'unauthenticated' => 'غير مصرح به',

    'no_permission' => 'ليس لديك الصلاحية للقيام بهذا الإجراء',
    'not_found' => 'السجل غير موجود',
    'operation_success' => 'تمت العملية بنجاح',
    'operation_failed' => 'فشلت العملية',

    // Admin Groups
    'admin_groups_fetched' => 'تم جلب مجموعات الإدارة بنجاح',
    'admin_group_fetched' => 'تم جلب مجموعة الإدارة بنجاح',
    'admin_group_created' => 'تم إنشاء مجموعة الإدارة بنجاح',
    'admin_group_updated' => 'تم تحديث مجموعة الإدارة بنجاح',
    'admin_group_deleted' => 'تم حذف مجموعة الإدارة بنجاح',

    // Permissions
    'permissions_required' => 'الصلاحيات مطلوبة',
    'permissions_array' => 'يجب أن تكون الصلاحيات مصفوفة',
    'permission_not_found' => 'الصلاحية غير موجودة',
    'permissions_updated' => 'تم تحديث الصلاحيات بنجاح',

    // Validation
    'title_en_required' => 'العنوان باللغة الإنجليزية مطلوب',
    'title_en_unique' => 'العنوان باللغة الإنجليزية موجود مسبقاً',
    'title_ar_required' => 'العنوان باللغة العربية مطلوب',

    // Invoices
    'invoices_fetched' => 'تم جلب الفواتير بنجاح',
    'invoice_fetched' => 'تم جلب الفاتورة بنجاح',
    'invoice_created' => 'تم إنشاء الفاتورة بنجاح',
    'invoice_updated' => 'تم تحديث الفاتورة بنجاح',
    'invoice_deleted' => 'تم حذف الفاتورة بنجاح',

    // Clients
    'clients_fetched' => 'تم جلب العملاء بنجاح',
    'client_fetched' => 'تم جلب العميل بنجاح',
    'client_created' => 'تم إنشاء العميل بنجاح',
    'client_updated' => 'تم تحديث العميل بنجاح',
    'client_deleted' => 'تم حذف العميل بنجاح',
];
