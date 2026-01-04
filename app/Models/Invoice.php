<?php

namespace App\Models;

use App\Constants\Constants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'user_id', // تمت إضافته
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'currency',
        'notes',
        'terms',
        'footer',
        'sent_at',
        'paid_at',
        'created_by',
        'is_active'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', Constants::ACTIVE);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', Constants::INVOICE_STATUS_DRAFT);
    }

    public function scopeSent($query)
    {
        return $query->where('status', Constants::INVOICE_STATUS_SENT);
    }

    public function scopePaid($query)
    {
        return $query->where('status', Constants::INVOICE_STATUS_PAID);
    }

    public function scopeOverdue($query)
    {
        return $query->where(function($q) {
            $q->where('status', Constants::INVOICE_STATUS_OVERDUE)
              ->orWhere(function($query) {
                  $query->where('status', Constants::INVOICE_STATUS_SENT)
                        ->where('due_date', '<', now());
              });
        });
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('invoice_number', 'like', "%{$search}%")
              ->orWhereHas('client', function($client) use ($search) {
                  $client->where('name', 'like', "%{$search}%")
                         ->orWhere('company_name', 'like', "%{$search}%");
              });
        });
    }

    // Relations
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user() // تمت إضافته
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Methods
    public function generateInvoiceNumber()
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "INV-{$year}{$month}-";

        $lastInvoice = self::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1001;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function markAsSent()
    {
        $this->update([
            'status' => Constants::INVOICE_STATUS_SENT,
            'sent_at' => now()
        ]);
    }

    public function markAsPaid()
    {
        $this->update([
            'status' => Constants::INVOICE_STATUS_PAID,
            'paid_at' => now()
        ]);
    }

    public function isOverdue()
    {
        return $this->status === Constants::INVOICE_STATUS_OVERDUE ||
               ($this->status === Constants::INVOICE_STATUS_SENT &&
                $this->due_date < now());
    }

    public function calculateTotals()
    {
        $subtotal = $this->items()->sum('total');
        $taxAmount = $this->tax_amount;
        $discountAmount = $this->discount_amount;
        $total = $subtotal + $taxAmount - $discountAmount;

        $this->update([
            'subtotal' => $subtotal,
            'total' => $total
        ]);

        return $total;
    }
}
