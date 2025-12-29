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
        'notes'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relations
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // Methods
    public function calculateTotal()
    {
        $subtotal = $this->quantity * $this->unit_price;
        $taxAmount = $subtotal * ($this->tax_rate / 100);
        $this->total = $subtotal + $taxAmount;

        return $this->total;
    }
}
