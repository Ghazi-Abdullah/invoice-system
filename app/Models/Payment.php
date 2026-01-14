<?php

namespace App\Models;

use App\Constants\Constants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'client_id',
        'user_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'payment_gateway',
        'stripe_payment_intent_id',
        'stripe_checkout_session_id',
        'stripe_customer_id',
        'metadata',
        'failure_reason',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    // ============ SCOPES (بنفس نمط Invoice.php) ============
    public function scopePending($query)
    {
        return $query->where('status', Constants::PAYMENT_STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', Constants::PAYMENT_STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', Constants::PAYMENT_STATUS_FAILED);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('stripe_payment_intent_id', 'like', "%{$search}%")
              ->orWhereHas('invoice', function($invoice) use ($search) {
                  $invoice->where('invoice_number', 'like', "%{$search}%");
              })
              ->orWhereHas('client', function($client) use ($search) {
                  $client->where('name', 'like', "%{$search}%")
                         ->orWhere('company_name', 'like', "%{$search}%");
              });
        });
    }

    // ============ RELATIONS (بنفس نمط Invoice.php) ============
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ============ METHODS ============
    public function markAsCompleted($paymentMethod = 'card')
    {
        $this->update([
            'status' => Constants::PAYMENT_STATUS_COMPLETED,
            'payment_method' => $paymentMethod,
            'paid_at' => now(),
        ]);

        // تحديث حالة الفاتورة
        if ($this->invoice) {
            $this->invoice->update([
                'status' => Constants::INVOICE_STATUS_PAID,
                'paid_at' => now(),
            ]);
        }

        return $this;
    }

    public function markAsFailed($reason = null)
    {
        $this->update([
            'status' => Constants::PAYMENT_STATUS_FAILED,
            'failure_reason' => $reason,
        ]);

        return $this;
    }

    public function isSuccessful()
    {
        return $this->status === Constants::PAYMENT_STATUS_COMPLETED;
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }
}
