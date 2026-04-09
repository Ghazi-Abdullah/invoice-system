<?php

namespace App\Http\Requests\Admin\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'invoice_number' => 'nullable|unique:invoices,invoice_number',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'status' => 'required|in:draft,sent,paid,overdue',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'payment_date' => 'nullable|date',
            'payment_notes' => 'nullable|string',
            'enable_stripe_checkout' => 'nullable|boolean', // إضافة قاعدة التحقق
            'subtotal' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
        ];
    }

    public function messages()
    {
        return [
            'client_id.required' => 'الرجاء اختيار عميل',
            'client_id.exists' => 'العميل المحدد غير موجود',
            'invoice_date.required' => 'تاريخ الإصدار مطلوب',
            'invoice_date.date' => 'تاريخ الإصدار غير صالح',
            'due_date.required' => 'تاريخ الاستحقاق مطلوب',
            'due_date.date' => 'تاريخ الاستحقاق غير صالح',
            'due_date.after_or_equal' => 'تاريخ الاستحقاق يجب أن يكون بعد تاريخ الإصدار أو مساوياً له',
            'status.required' => 'حالة الفاتورة مطلوبة',
            'status.in' => 'حالة الفاتورة غير صالحة',
            'items.required' => 'يجب إضافة عنصر واحد على الأقل',
            'items.array' => 'العناصر يجب أن تكون مصفوفة',
            'items.min' => 'يجب إضافة عنصر واحد على الأقل',
            'items.*.description.required' => 'وصف العنصر مطلوب',
            'items.*.quantity.required' => 'كمية العنصر مطلوبة',
            'items.*.quantity.numeric' => 'الكمية يجب أن تكون رقم',
            'items.*.quantity.min' => 'الكمية يجب أن تكون أكبر من 0',
            'items.*.unit_price.required' => 'سعر العنصر مطلوب',
            'items.*.unit_price.numeric' => 'السعر يجب أن يكون رقم',
            'items.*.unit_price.min' => 'السعر يجب أن يكون أكبر من أو يساوي 0',
            'enable_stripe_checkout.boolean' => 'قيمة Stripe Checkout غير صالحة',
        ];
    }
}
