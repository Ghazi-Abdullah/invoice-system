<?php

namespace App\Models;

use App\Constants\Constants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_menu_id',
        'admin_sub_menu_id',
        'parent_id',
        'title',
        'description_en',
        'description_ar',
        'is_parent',
        'is_active'
    ];

    protected $casts = [
        'is_parent' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', Constants::ACTIVE);
    }

    public function scopeParent($query)
    {
        return $query->where('is_parent', true);
    }

    public function scopeChild($query)
    {
        return $query->where('is_parent', false);
    }

    // Relations
    public function menu()
    {
        return $this->belongsTo(AdminMenu::class, 'admin_menu_id');
    }

    public function subMenu()
    {
        return $this->belongsTo(AdminSubMenu::class, 'admin_sub_menu_id');
    }

    public function parent()
    {
        return $this->belongsTo(AdminPermission::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(AdminPermission::class, 'parent_id');
    }

    public function adminGroups()
    {
        return $this->belongsToMany(
            AdminGroup::class,
            'admin_group_permissions',
            'admin_permission_id',
            'admin_group_id'
        )->withTimestamps();
    }
}
