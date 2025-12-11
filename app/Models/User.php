<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'admin_group_id',
        'is_active'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function group()
    {
        return $this->belongsTo(AdminGroup::class, 'admin_group_id');
    }

    public function hasPermission($permissionTitle)
    {
        if (!$this->group) {
            return false;
        }

        return $this->group->permissions()->where('title', $permissionTitle)->exists();
    }

    public function getPermissionsAttribute()
    {
        if (!$this->group) {
            return [];
        }

        return $this->group->permissions->pluck('title')->toArray();
    }

    public function getMenusAttribute()
    {
        if (!$this->group) {
            return [];
        }

        $permissions = $this->group->permissions;

        $menuIds = [];
        $subMenuIds = [];

        foreach ($permissions as $permission) {
            if ($permission->admin_menu_id) {
                $menuIds[] = $permission->admin_menu_id;
            }
            if ($permission->admin_sub_menu_id) {
                $subMenuIds[] = $permission->admin_sub_menu_id;
            }
        }

        $menuIds = array_unique($menuIds);
        $subMenuIds = array_unique($subMenuIds);

        $menus = AdminMenu::whereIn('id', $menuIds)
            ->with(['subMenus' => function($query) use ($subMenuIds) {
                $query->whereIn('id', $subMenuIds);
            }])
            ->orderBy('sort_order')
            ->get();

        return $menus;
    }
}
