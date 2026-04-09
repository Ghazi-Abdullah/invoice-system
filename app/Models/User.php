<?php

namespace App\Models;

use App\Constants\Constants;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'img',
        'password',
        'phone',
        'address',
        'company_name',
        'tax_number',
        'is_active',
        'admin_group_id',
        'email_verified_at',
        'remember_token',
        'otp',
        'otp_via',
        'otp_created_at',
        'otp_attempts',
        'otp_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'otp_created_at'  => 'datetime',
        'otp_verified_at' => 'datetime',
    ];

    protected $appends = ['img_url'];

    public function getImgUrlAttribute()
    {
        return $this->img ? asset('storage/' . $this->img) : null;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', Constants::ACTIVE);
    }

    public function scopeClients($query)
    {
        return $query->where('admin_group_id', Constants::CLIENT_GROUP_ID);
    }

    public function scopeStaff($query)
    {
        return $query->where('admin_group_id', '!=', Constants::CLIENT_GROUP_ID);
    }

    // Relations
    public function adminGroup()
    {
        return $this->belongsTo(AdminGroup::class, 'admin_group_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'user_id');
    }

    public function createdInvoices()
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    public function activities()
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    // Methods
    public function isSuperAdmin()
    {
        return $this->admin_group_id === Constants::SUPER_ADMIN_GROUP_ID;
    }

    public function isAdmin()
    {
        return $this->admin_group_id === Constants::ADMIN_GROUP_ID;
    }

    public function isClient()
    {
        return $this->admin_group_id === Constants::CLIENT_GROUP_ID;
    }

    // تغيير اسم الدالة لتجنب التعارض مع دالة can() الأصلية
    public function hasPermission($permission)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $adminGroup = $this->adminGroup;
        if (!$adminGroup) {
            return false;
        }

        return $adminGroup->permissions()
            ->where('title', $permission)
            ->exists();
    }

    // إضافة دالة can() متوافقة مع الوالد
    public function can($ability, $arguments = [])
    {
        // استخدام نظام الصلاحيات الخاص بنا
        return $this->hasPermission($ability);
    }
}
