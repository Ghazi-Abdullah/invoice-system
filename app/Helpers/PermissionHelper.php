<?php

namespace App\Helpers;

use App\Models\Permission;
use App\Models\AdminGroupPermission;

class PermissionHelper
{
    public static function checkPermission($permissionTitle)
    {
        $user = auth()->user();

        if (!$user || !$user->admin_group_id) {
            return false;
        }

        $permission = Permission::where('title', $permissionTitle)->first();

        if (!$permission) {
            return false;
        }

        return AdminGroupPermission::where([
            'admin_group_id' => $user->admin_group_id,
            'permission_id' => $permission->id
        ])->exists();
    }

    public static function getUserPermissions()
    {
        $user = auth()->user();

        if (!$user || !$user->admin_group_id) {
            return [];
        }

        return $user->group->permissions->pluck('title')->toArray();
    }

    public static function hasAnyPermission(array $permissions)
    {
        foreach ($permissions as $permission) {
            if (self::checkPermission($permission)) {
                return true;
            }
        }
        return false;
    }
}
