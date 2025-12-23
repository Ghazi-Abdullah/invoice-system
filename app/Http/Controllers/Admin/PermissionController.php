<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use App\Models\AdminPermission;
use App\Models\AdminGroup;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    use ResponseTrait;

    public function index(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_PERMISSIONS)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $permissions = AdminPermission::with(['menu', 'subMenu', 'children'])
            ->orderBy('admin_menu_id')
            ->orderBy('admin_sub_menu_id')
            ->orderBy('parent_id')
            ->orderBy('id')
            ->get();

        return $this->successResponse(__('messages.permissions_fetched'), $permissions);
    }

    public function getGroupPermissions($groupId)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_PERMISSIONS)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $group = AdminGroup::with('permissions')->find($groupId);

        if (!$group) {
            return $this->failureResponse(__('messages.not_found'), null, 404);
        }

        $allPermissions = AdminPermission::with(['menu', 'subMenu'])
            ->orderBy('admin_menu_id')
            ->orderBy('admin_sub_menu_id')
            ->orderBy('parent_id')
            ->orderBy('id')
            ->get();

        $groupPermissions = $group->permissions->pluck('id')->toArray();

        return $this->successResponse(__('messages.permissions_fetched'), [
            'permissions' => $allPermissions,
            'selected_permissions' => $groupPermissions,
            'group' => $group
        ]);
    }

    public function updateGroupPermissions(Request $request, $groupId)
    {
        if (!PermissionHelper::checkPermission(Constants::MANAGE_PERMISSIONS)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:admin_permissions,id'
        ]);

        $group = AdminGroup::find($groupId);

        if (!$group) {
            return $this->failureResponse(__('messages.not_found'), null, 404);
        }

        // Cannot modify super admin group permissions
        if ($group->id == Constants::SUPER_ADMIN_GROUP_ID) {
            return $this->failureResponse(__('messages.cannot_modify_super_admin'), null, 403);
        }

        $group->permissions()->sync($request->permissions);

        return $this->successResponse(__('messages.permissions_updated'), [
            'group' => $group->load('permissions')
        ]);
    }

    public function getMenus()
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $menus = \App\Models\AdminMenu::with(['subMenus' => function($query) {
                $query->where('is_active', 1)->orderBy('sort_order');
            }])
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();
        } else {
            // Get user's permissions
            $permissions = $user->adminGroup->permissions()
                ->where('is_active', 1)
                ->pluck('id')
                ->toArray();

            // Get sub menus that have these permissions
            $subMenuIds = \App\Models\AdminPermission::whereIn('id', $permissions)
                ->whereNotNull('admin_sub_menu_id')
                ->pluck('admin_sub_menu_id')
                ->unique()
                ->toArray();

            $menus = \App\Models\AdminMenu::with(['subMenus' => function($query) use ($subMenuIds) {
                $query->whereIn('id', $subMenuIds)
                    ->where('is_active', 1)
                    ->orderBy('sort_order');
            }])
            ->whereHas('subMenus', function($query) use ($subMenuIds) {
                $query->whereIn('id', $subMenuIds)
                    ->where('is_active', 1);
            })
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();
        }

        return $this->successResponse(__('messages.menus_fetched'), $menus);
    }

    public function getUserPermissions()
    {
        $user = auth()->user();
        $permissions = [];

        if ($user->isSuperAdmin()) {
            $permissions = AdminPermission::where('is_active', 1)
                ->pluck('title')
                ->toArray();
        } else {
            $permissions = $user->adminGroup->permissions()
                ->where('is_active', 1)
                ->pluck('title')
                ->toArray();
        }

        return $this->successResponse(__('messages.permissions_fetched'), $permissions);
    }
}
