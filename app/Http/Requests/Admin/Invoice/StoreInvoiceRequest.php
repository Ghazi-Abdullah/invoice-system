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
            'invoice_number' => 'nullable|string|unique:invoices,invoice_number',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'status' => 'nullable|in:draft,sent,paid,overdue',
        ];
    }

    public function messages()
    {
        return [
            'client_id.required' => 'يرجى اختيار عميل',
            'client_id.exists' => 'العميل المحدد غير موجود',
            'invoice_date.required' => 'يرجى إدخال تاريخ الفاتورة',
            'due_date.required' => 'يرجى إدخال تاريخ الاستحقاق',
            'due_date.after_or_equal' => 'تاريخ الاستحقاق يجب أن يكون بعد تاريخ الفاتورة أو مساوياً له',
            'items.required' => 'يجب أن تحتوي الفاتورة على عنصر واحد على الأقل',
            'items.*.description.required' => 'يرجى إدخال وصف للعنصر',
            'items.*.quantity.required' => 'يرجى إدخال كمية للعنصر',
            'items.*.quantity.min' => 'يجب أن تكون الكمية أكبر من صفر',
            'items.*.unit_price.required' => 'يرجى إدخال سعر للعنصر',
            'items.*.unit_price.min' => 'يجب أن يكون السعر أكبر من أو يساوي صفر',
            'subtotal.required' => 'يرجى إدخال المجموع الفرعي',
            'tax_amount.required' => 'يرجى إدخال قيمة الضريبة',
            'total.required' => 'يرجى إدخال الإجمالي',
        ];
    }
}
