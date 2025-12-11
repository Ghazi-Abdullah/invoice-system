<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminGroup;
use App\Models\AdminGroupPermission;
use App\Models\AdminPermission;
use Illuminate\Http\Request;

class AdminGroupController extends Controller
{
    public function index()
    {
        $groups = AdminGroup::with('permissions')->get();

        return response()->json([
            'status' => true,
            'message' => 'Groups retrieved successfully',
            'data' => $groups
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title_en' => 'required|string|max:255',
            'title_ar' => 'required|string|max:255',
            'is_active' => 'boolean'
        ]);

        $group = AdminGroup::create($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Group created successfully',
            'data' => $group
        ], 201);
    }

    public function show(AdminGroup $adminGroup)
    {
        $adminGroup->load('permissions');

        return response()->json([
            'status' => true,
            'message' => 'Group retrieved successfully',
            'data' => $adminGroup
        ]);
    }

    public function update(Request $request, AdminGroup $adminGroup)
    {
        $request->validate([
            'title_en' => 'sometimes|string|max:255',
            'title_ar' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean'
        ]);

        $adminGroup->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'Group updated successfully',
            'data' => $adminGroup->load('permissions')
        ]);
    }

    public function destroy(AdminGroup $adminGroup)
    {
        if ($adminGroup->users()->count() > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete group that has users'
            ], 400);
        }

        $adminGroup->delete();

        return response()->json([
            'status' => true,
            'message' => 'Group deleted successfully'
        ]);
    }

    public function updatePermissions(Request $request, AdminGroup $adminGroup)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $adminGroup->permissions()->sync($request->permissions);

        return response()->json([
            'status' => true,
            'message' => 'Permissions updated successfully'
        ]);
    }

    public function getAvailablePermissions()
    {
        $permissions = AdminPermission::with(['menu', 'subMenu'])
            ->orderBy('admin_menu_id')
            ->orderBy('admin_sub_menu_id')
            ->orderBy('parent_id')
            ->orderBy('id')
            ->get()
            ->groupBy(function ($permission) {
                if ($permission->menu && $permission->subMenu) {
                    return $permission->menu->title_en . ' - ' . $permission->subMenu->title_en;
                } elseif ($permission->menu) {
                    return $permission->menu->title_en . ' - General';
                }
                return 'Uncategorized';
            });

        return response()->json([
            'status' => true,
            'message' => 'Permissions retrieved successfully',
            'data' => $permissions
        ]);
    }
}
