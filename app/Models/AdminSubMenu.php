<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminSubMenu extends Model
{
    use HasFactory;

    protected $fillable = ['admin_menu_id', 'title_en', 'title_ar', 'link', 'sort_order'];

    public function menu()
    {
        return $this->belongsTo(AdminMenu::class);
    }

    public function permissions()
    {
        return $this->hasMany(AdminPermission::class);
    }
}
