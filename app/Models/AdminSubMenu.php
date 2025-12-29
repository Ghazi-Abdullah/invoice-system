<?php

namespace App\Models;

use App\Constants\Constants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminSubMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_menu_id',
        'title_en',
        'title_ar',
        'link',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', Constants::ACTIVE);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    // Relations
    public function menu()
    {
        return $this->belongsTo(AdminMenu::class, 'admin_menu_id');
    }

    public function permission()
    {
        return $this->belongsTo(AdminPermission::class, 'admin_permission_id');
    }
}
