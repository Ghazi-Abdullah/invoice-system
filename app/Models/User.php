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
        'password' => 'hashed',
        'is_active' => 'boolean'
    ];

    /**
     * العلاقة مع مجموعة الإدارة
     */
    public function group()
    {
        return $this->belongsTo(AdminGroup::class, 'admin_group_id');
    }

    /**
     * الحصول على جميع صلاحيات المستخدم من مجموعته
     */
    public function getPermissionsAttribute()
    {
        if (!$this->group) {
            return collect();
        }

        // تحميل صلاحيات المجموعة إذا لم تكن محملة
        if (!$this->group->relationLoaded('permissions')) {
            $this->group->load('permissions');
        }

        return $this->group->permissions->pluck('title');
    }

    /**
     * التحقق مما إذا كان المستخدم لديه صلاحية معينة
     */
    public function hasPermission($permission)
    {
        // إذا كان المستخدم غير نشط، لا يملك أي صلاحيات
        if (!$this->is_active) {
            \Log::warning('User is not active', ['user_id' => $this->id]);
            return false;
        }

        // إذا كان المستخدم في المجموعة 1 (مدير) فلديه جميع الصلاحيات
        if ($this->admin_group_id == 1) {
            \Log::info('User is admin, has all permissions', ['user_id' => $this->id]);
            return true;
        }

        // التحقق من صلاحيات المجموعة
        if (!$this->group) {
            \Log::warning('User has no group', ['user_id' => $this->id]);
            return false;
        }

        // تحميل العلاقة إذا لم تكن محملة
        if (!$this->group->relationLoaded('permissions')) {
            $this->group->load('permissions');
        }

        $hasPermission = $this->group->permissions->contains('title', $permission);

        \Log::info('Permission check', [
            'user_id' => $this->id,
            'permission' => $permission,
            'has_permission' => $hasPermission,
            'group_permissions' => $this->group->permissions->pluck('title')->toArray()
        ]);

        return $hasPermission;
    }

    /**
     * التحقق مما إذا كان المستخدم لديه أي من الصلاحيات المحددة
     */
    public function hasAnyPermission($permissions)
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * التحقق مما إذا كان المستخدم لديه جميع الصلاحيات المحددة
     */
    public function hasAllPermissions($permissions)
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }
}
