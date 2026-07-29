<?php

namespace App\Models;

use App\Constants\Constants;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Installment extends Model
{
    use HasFactory, SoftDeletes;

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
     * ØªØ³Ø¬ÙŠÙ„ Ø§Ù„Ù‚Ø³Ø· ÙƒÙ…Ø¯ÙÙˆØ¹ + Ø¥Ù†Ø´Ø§Ø¡ Ø³Ø¬Ù„ Payment Ù…Ø±ØªØ¨Ø· Ø¨Ù‡ (Ø¨Ø¯ÙˆÙ† ØªØ¹Ù„ÙŠÙ… Ø§Ù„ÙØ§ØªÙˆØ±Ø©
     * ÙƒØ§Ù…Ù„Ø© ÙƒÙ…Ø¯ÙÙˆØ¹Ø© â€” Ù‡Ø°Ø§ Ù…Ø³Ø¤ÙˆÙ„ÙŠØ© InstallmentPlanRepository Ø¨Ø¹Ø¯ Ø§Ù„ØªØ£ÙƒØ¯ Ù…Ù†
     * Ø³Ø¯Ø§Ø¯ Ø¬Ù…ÙŠØ¹ Ø§Ù„Ø£Ù‚Ø³Ø§Ø·).
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

