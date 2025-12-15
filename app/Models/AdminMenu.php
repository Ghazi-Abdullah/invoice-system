<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminMenu extends Model
{
    use HasFactory;

    protected $fillable = ['title_en', 'title_ar', 'link', 'icon_class', 'sort_order'];

    public function subMenus()
    {
        return $this->hasMany(AdminSubMenu::class);
    }

    public function permissions()
    {
        return $this->hasMany(AdminPermission::class);
    }
}
