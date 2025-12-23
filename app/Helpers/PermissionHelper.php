<?php

namespace App\Helpers;

use App\Models\User;
use App\Constants\Constants;

class PermissionHelper
{
    public static function checkPermission($permission)
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Super admin has all permissions
        if ($user->admin_group_id === Constants::SUPER_ADMIN_GROUP_ID) {
            return true;
        }

        // Check if user's admin group has the permission
        $adminGroup = $user->adminGroup;
        if (!$adminGroup) {
            return false;
        }

        return $adminGroup->permissions()
            ->where('title', $permission)
            ->exists();
    }

    public static function getPermissions()
    {
        $user = auth()->user();

        if (!$user) {
            return [];
        }

        if ($user->admin_group_id === Constants::SUPER_ADMIN_GROUP_ID) {
            // Return all permissions for super admin
            return \App\Models\AdminPermission::where('is_active', 1)
                ->pluck('title')
                ->toArray();
        }

        $adminGroup = $user->adminGroup;
        if (!$adminGroup) {
            return [];
        }

        return $adminGroup->permissions()
            ->where('is_active', 1)
            ->pluck('title')
            ->toArray();
    }

    public static function can($permission)
    {
        return self::checkPermission($permission);
    }

    public static function canAny(array $permissions)
    {
        foreach ($permissions as $permission) {
            if (self::checkPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    public static function canAll(array $permissions)
    {
        foreach ($permissions as $permission) {
            if (!self::checkPermission($permission)) {
                return false;
            }
        }
        return true;
    }
}
