<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'total',
        'item_type',
        'notes',
    ];

    protected $casts = [
        'quantity'    => 'decimal:2',
        'unit_price'  => 'decimal:2',
        'tax_rate'    => 'decimal:2',
        'total'       => 'decimal:2',
    ];

    // Relations
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // ✅ Auto-calculate total before saving
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            $item->total = $item->calculateTotal();
        });

        static::updating(function ($item) {
            if ($item->isDirty(['quantity', 'unit_price', 'tax_rate'])) {
                $item->total = $item->calculateTotal();
            }
        });
    }

    // ✅ Unified calculation (matches repository logic)
    public function calculateTotal(): float
    {
        $subtotal  = (float) $this->quantity * (float) $this->unit_price;
        $taxAmount = $subtotal * ((float) ($this->tax_rate ?? 0) / 100);

        return $subtotal + $taxAmount;
    }

    // ✅ Getters for frontend
    public function getSubtotalAttribute(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }

    public function getTaxAmountAttribute(): float
    {
        return $this->getSubtotalAttribute() * ((float) ($this->tax_rate ?? 0) / 100);
    }
}
