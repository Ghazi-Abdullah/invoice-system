<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\AdminPermission;
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
            ->where('is_active', Constants::ACTIVE)
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
            return AdminPermission::where('is_active', Constants::ACTIVE)
                ->pluck('title')
                ->toArray();
        }

        $adminGroup = $user->adminGroup;
        if (!$adminGroup) {
            return [];
        }

        return $adminGroup->permissions()
            ->where('is_active', Constants::ACTIVE)
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

    /**
     * Get user permissions with details
     */
    public static function getPermissionsWithDetails()
    {
        $user = auth()->user();

        if (!$user) {
            return [];
        }

        if ($user->admin_group_id === Constants::SUPER_ADMIN_GROUP_ID) {
            // Return all permissions for super admin
            return AdminPermission::where('is_active', Constants::ACTIVE)
                ->get(['id', 'title', 'description_en', 'description_ar', 'is_parent'])
                ->toArray();
        }

        $adminGroup = $user->adminGroup;
        if (!$adminGroup) {
            return [];
        }

        return $adminGroup->permissions()
            ->where('is_active', Constants::ACTIVE)
            ->get(['id', 'title', 'description_en', 'description_ar', 'is_parent'])
            ->toArray();
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin()
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return $user->admin_group_id === Constants::SUPER_ADMIN_GROUP_ID ||
               $user->admin_group_id === Constants::ADMIN_GROUP_ID;
    }

    /**
     * Check if user is super admin
     */
    public static function isSuperAdmin()
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return $user->admin_group_id === Constants::SUPER_ADMIN_GROUP_ID;
    }

    /**
     * Check if user is client
     */
    public static function isClient()
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return $user->admin_group_id === Constants::CLIENT_GROUP_ID;
    }

    /**
     * Get user's admin group
     */
    public static function getUserAdminGroup()
    {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        return $user->adminGroup;
    }
}
