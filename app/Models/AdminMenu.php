<?php

namespace App\Models;

use App\Constants\Constants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'title_en',
        'title_ar',
        'icon',
        'order',
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
        return $query->orderBy('order', 'asc');
    }

    // Relations
    public function subMenus()
    {
        return $this->hasMany(AdminSubMenu::class);
    }
}
