<?php

namespace App\Models;

use App\Constants\Constants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'company_name',
        'tax_number',
        'payment_terms',
        'currency',
        'notes',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', Constants::ACTIVE);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('company_name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function paidInvoices()
    {
        return $this->hasMany(Invoice::class)->where('status', Constants::INVOICE_STATUS_PAID);
    }

    // Methods
    public function totalInvoiced()
    {
        return $this->invoices()->sum('total');
    }

    public function totalPaid()
    {
        return $this->paidInvoices()->sum('total');
    }

    public function totalDue()
    {
        return $this->totalInvoiced() - $this->totalPaid();
    }
}
