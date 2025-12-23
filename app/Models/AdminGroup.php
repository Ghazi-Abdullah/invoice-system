<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'title_en',
        'title_ar',
        'description',
        'is_active',
        'is_system',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    // Relations
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(AdminPermission::class, 'admin_group_permissions');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeNonSystem($query)
    {
        return $query->where('is_system', false);
    }

    // Helper methods
    public function hasPermission($permissionTitle)
    {
        return $this->permissions()->where('title', $permissionTitle)->exists();
    }

    public function getPermissionNames()
    {
        return $this->permissions()->pluck('title')->toArray();
    }
}
