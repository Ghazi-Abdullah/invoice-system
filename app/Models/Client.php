<?php

namespace App\Models;

use App\Constants\Constants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory;

    /**
     * ✅ $fillable محدد بدقة
     */
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
        'created_by',
    ];

    /**
     * ✅ $hidden — إخفاء الحقول الحساسة من الاستجابات
     */
    protected $hidden = [
        'created_by',  // لا يُكشف في الـ API
    ];

    /**
     * ✅ Casts صحيحة — لا array خاطئ كما كان في الكود الأصلي
     * الكود القديم كان يضع 'id', 'created_at' إلخ كـ casts وهو خطأ
     */
    protected $casts = [
        'is_active'  => 'boolean',
        'created_by' => 'integer',
    ];

    // ================================================================
    // Scopes
    // ================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', Constants::ACTIVE);
    }

    /**
     * ✅ Search scope محمي — الحد بـ 100 حرف
     */
    public function scopeSearch($query, string $search)
    {
        $search = substr(trim($search), 0, 100);

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('company_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    // ================================================================
    // Relations
    // ================================================================

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function paidInvoices()
    {
        return $this->hasMany(Invoice::class)
            ->where('status', Constants::INVOICE_STATUS_PAID);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // ================================================================
    // Methods
    // ================================================================

    public function totalInvoiced(): float
    {
        return (float) $this->invoices()->sum('total');
    }

    public function totalPaid(): float
    {
        return (float) $this->paidInvoices()->sum('total');
    }

    public function totalDue(): float
    {
        return $this->totalInvoiced() - $this->totalPaid();
    }
}
