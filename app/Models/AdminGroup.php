<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminGroup extends Model
{
    use HasFactory;

    protected $fillable = ['title_en', 'title_ar', 'is_active'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(AdminPermission::class, 'admin_group_permissions', 'admin_group_id', 'admin_permission_id');
    }
}
