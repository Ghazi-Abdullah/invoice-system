<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminGroup extends Model
{
    protected $fillable = ['title_en', 'title_ar', 'is_active'];

    public function permissions()
    {
        return $this->belongsToMany(
            AdminPermission::class,
            'admin_group_permissions',
            'admin_group_id',
            'admin_permission_id'
        );
    }

    public function users()
    {
        return $this->hasMany(User::class, 'admin_group_id');
    }

    public function hasPermission($permissionName)
    {
        return $this->permissions()->where('title', $permissionName)->exists();
    }
}
