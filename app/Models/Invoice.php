<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_total',
        'total_amount',
        'status',
        'notes',
        'terms',
        'user_id'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * العلاقة مع العميل
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * العلاقة مع العناصر
     */
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * العلاقة مع المستخدم الذي أنشأ الفاتورة
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الحصول على الحالة بالعربية
     */
    public function getStatusTextAttribute()
    {
        $statuses = [
            'draft' => 'مسودة',
            'sent' => 'مرسلة',
            'paid' => 'مدفوعة',
            'overdue' => 'متأخرة',
            'cancelled' => 'ملغية'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * التحقق مما إذا كانت الفاتورة متأخرة
     */
    public function getIsOverdueAttribute()
    {
        if ($this->status === 'paid') {
            return false;
        }

        return $this->due_date < now();
    }

    /**
     * Scope للفواتير النشطة
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled']);
    }

    /**
     * Scope للبحث
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('invoice_number', 'like', "%{$search}%")
              ->orWhereHas('client', function ($q2) use ($search) {
                  $q2->where('name', 'like', "%{$search}%")
                     ->orWhere('email', 'like', "%{$search}%");
              });
        });
    }
}
