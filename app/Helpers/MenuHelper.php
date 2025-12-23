<?php

namespace App\Helpers;

use App\Models\AdminMenu;
use App\Models\AdminSubMenu;
use App\Constants\Constants;

class MenuHelper
{
    public static function getAdminMenu()
    {
        $user = auth()->user();

        if (!$user) {
            return [];
        }

        // Super admin gets all menus
        if ($user->admin_group_id === Constants::SUPER_ADMIN_GROUP_ID) {
            return AdminMenu::with(['subMenus' => function($query) {
                $query->where('is_active', Constants::ACTIVE)
                      ->orderBy('order', 'asc');
            }])
            ->where('is_active', Constants::ACTIVE)
            ->orderBy('order', 'asc')
            ->get();
        }

        // Get user's admin group
        $adminGroup = $user->adminGroup;
        if (!$adminGroup) {
            return [];
        }

        // Get permissions for the admin group
        $permissions = $adminGroup->permissions()->pluck('admin_permission_id')->toArray();

        // Get menus based on permissions
        return AdminMenu::with(['subMenus' => function($query) use ($permissions) {
                $query->where('is_active', Constants::ACTIVE)
                      ->whereIn('admin_permission_id', $permissions)
                      ->orderBy('order', 'asc');
            }])
            ->where('is_active', Constants::ACTIVE)
            ->whereHas('subMenus', function($query) use ($permissions) {
                $query->where('is_active', Constants::ACTIVE)
                      ->whereIn('admin_permission_id', $permissions);
            })
            ->orderBy('order', 'asc')
            ->get();
    }

    public static function getUserMenu()
    {
        // User menu is simpler, usually just dashboard, profile, etc.
        return [
            [
                'id' => 1,
                'title_en' => 'Dashboard',
                'title_ar' => 'لوحة التحكم',
                'icon' => 'fa-home',
                'route' => 'user.dashboard',
                'order' => 1
            ],
            [
                'id' => 2,
                'title_en' => 'My Invoices',
                'title_ar' => 'فواتيري',
                'icon' => 'fa-file-invoice',
                'route' => 'user.invoices.index',
                'order' => 2
            ],
            [
                'id' => 3,
                'title_en' => 'Profile',
                'title_ar' => 'الملف الشخصي',
                'icon' => 'fa-user',
                'route' => 'user.profile',
                'order' => 3
            ]
        ];
    }
}
