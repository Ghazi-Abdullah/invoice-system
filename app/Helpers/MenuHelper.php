<?php

namespace App\Helpers;

use App\Models\AdminMenu;
use App\Models\AdminSubMenu;

class MenuHelper
{
    public static function getUserMenus()
    {
        $user = auth()->user();

        if (!$user || !$user->admin_group_id) {
            return [];
        }

        $permissions = $user->group->permissions;

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
