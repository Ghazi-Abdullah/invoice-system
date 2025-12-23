<?php

namespace App\Models;

use App\Constants\Constants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'title_en',       // تغيير من 'name' إلى 'title_en'
        'title_ar',       // أضف هذا الحقل
        'description',
        'is_active',
        'is_system'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', Constants::ACTIVE);
    }

    public function scopeNonSystem($query)
    {
        return $query->where('is_system', false);
    }

    // Relations
    public function users()
    {
        return $this->hasMany(User::class, 'admin_group_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(
            AdminPermission::class,
            'admin_group_permissions',
            'admin_group_id',
            'admin_permission_id'
        )->withTimestamps();
    }

    // Accessor للحصول على الاسم حسب اللغة
    public function getNameAttribute()
    {
        // يمكن تعديل هذا بناءً على لغة التطبيق
        return app()->getLocale() === 'ar' ? $this->title_ar : $this->title_en;
    }

    // Methods
    public function canDelete()
    {
        if ($this->is_system || $this->id === Constants::SUPER_ADMIN_GROUP_ID) {
            return false;
        }

        return $this->users()->count() === 0;
    }
}
