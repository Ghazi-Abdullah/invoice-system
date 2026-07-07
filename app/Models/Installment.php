<?php

namespace App\Models;

use App\Constants\Constants;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Installment extends Model
{
    use HasFactory;

    protected $fillable = [
        'installment_plan_id',
        'installment_number',
        'due_date',
        'amount',
        'status',
        'payment_id',
        'paid_at',
    ];

    protected $casts = [
        'installment_plan_id' => 'integer',
        'installment_number'  => 'integer',
        'due_date'             => 'date',
        'amount'               => 'decimal:2',
        'payment_id'           => 'integer',
        'paid_at'              => 'datetime',
    ];

    // ================================================================
    // Scopes
    // ================================================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')->where('due_date', '<', now()->toDateString());
    }

    // ================================================================
    // Relations
    // ================================================================

    public function plan()
    {
        return $this->belongsTo(InstallmentPlan::class, 'installment_plan_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    // ================================================================
    // Methods
    // ================================================================

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date->lt(Carbon::now()->startOfDay());
    }

    /**
     * تسجيل القسط كمدفوع + إنشاء سجل Payment مرتبط به (بدون تعليم الفاتورة
     * كاملة كمدفوعة — هذا مسؤولية InstallmentPlanRepository بعد التأكد من
     * سداد جميع الأقساط).
     */
    public function markAsPaid(string $paymentMethod = 'cash'): Payment
    {
        $payment = Payment::create([
            'invoice_id'      => $this->plan->invoice_id,
            'client_id'       => $this->plan->invoice->client_id,
            'user_id'         => auth('sanctum')->id(),
            'amount'          => $this->amount,
            'currency'        => $this->plan->invoice->currency ?? Constants::CURRENCY_SAR,
            'status'          => Constants::PAYMENT_STATUS_COMPLETED,
            'payment_method'  => $paymentMethod,
            'payment_gateway' => 'installment',
            'paid_at'         => now(),
        ]);

        $this->update([
            'status'     => 'paid',
            'payment_id' => $payment->id,
            'paid_at'    => now(),
        ]);

        return $payment;
    }
}
