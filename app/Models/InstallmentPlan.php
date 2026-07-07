<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InstallmentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'number_of_installments',
        'interest_rate',
        'original_amount',
        'interest_amount',
        'total_amount',
        'start_date',
        'frequency',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_id'              => 'integer',
        'number_of_installments'  => 'integer',
        'interest_rate'           => 'decimal:2',
        'original_amount'         => 'decimal:2',
        'interest_amount'         => 'decimal:2',
        'total_amount'            => 'decimal:2',
        'start_date'              => 'date',
        'created_by'              => 'integer',
    ];

    // ================================================================
    // Scopes
    // ================================================================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ================================================================
    // Relations
    // ================================================================

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function installments()
    {
        return $this->hasMany(Installment::class)->orderBy('installment_number');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ================================================================
    // Methods
    // ================================================================

    public function paidInstallmentsCount(): int
    {
        return $this->installments()->where('status', 'paid')->count();
    }

    public function isFullyPaid(): bool
    {
        return $this->installments()->where('status', '!=', 'paid')->doesntExist();
    }

    public function remainingAmount(): float
    {
        return (float) $this->installments()->where('status', '!=', 'paid')->sum('amount');
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
        $this->installments()->where('status', 'pending')->update(['status' => 'cancelled']);
    }
}
