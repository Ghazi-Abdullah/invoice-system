<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminPermission extends Model
{
    use HasFactory;

    protected $table = 'admin_permissions';

    protected $fillable = ['admin_menu_id', 'admin_sub_menu_id', 'parent_id', 'title', 'description_en', 'description_ar', 'is_parent'];

    public function menu()
    {
        return $this->belongsTo(AdminMenu::class, 'admin_menu_id');
    }

    public function subMenu()
    {
        return $this->belongsTo(AdminSubMenu::class, 'admin_sub_menu_id');
    }

    public function groups()
    {
        return $this->belongsToMany(AdminGroup::class, 'admin_group_permissions', 'admin_permission_id', 'admin_group_id');
    }
}
