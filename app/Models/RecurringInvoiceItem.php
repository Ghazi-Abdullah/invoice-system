<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RecurringInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'recurring_invoice_template_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'total',
        'item_type',
        'notes',
    ];

    protected $casts = [
        'recurring_invoice_template_id' => 'integer',
        'quantity'                      => 'decimal:2',
        'unit_price'                    => 'decimal:2',
        'tax_rate'                      => 'decimal:2',
        'total'                         => 'decimal:2',
    ];

    public function template()
    {
        return $this->belongsTo(RecurringInvoiceTemplate::class, 'recurring_invoice_template_id');
    }
}