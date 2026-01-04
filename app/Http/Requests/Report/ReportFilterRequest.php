<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:draft,sent,paid,overdue',
            'client_id' => 'nullable|exists:clients,id',
            'user_id' => 'nullable|exists:users,id',
            'per_page' => 'nullable|integer|min:5|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.date' => 'تاريخ البداية يجب أن يكون تاريخاً صالحاً',
            'end_date.date' => 'تاريخ النهاية يجب أن يكون تاريخاً صالحاً',
            'end_date.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية',
            'status.in' => 'حالة الفاتورة غير صالحة',
            'client_id.exists' => 'العميل المحدد غير موجود',
            'user_id.exists' => 'المستخدم المحدد غير موجود',
            'per_page.integer' => 'عدد العناصر في الصفحة يجب أن يكون رقماً',
            'per_page.min' => 'الحد الأدنى للعناصر في الصفحة هو 5',
            'per_page.max' => 'الحد الأقصى للعناصر في الصفحة هو 100'
        ];
    }
}
