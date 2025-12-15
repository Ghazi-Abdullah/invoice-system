<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminPermission;
use App\Models\AdminMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = AdminPermission::with(['menu', 'subMenu'])
            ->orderBy('admin_menu_id')
            ->orderBy('admin_sub_menu_id')
            ->orderBy('parent_id')
            ->orderBy('id')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Permissions retrieved successfully',
            'data' => $permissions
        ]);
    }

    public function getMenusWithPermissions()
    {
        $menus = AdminMenu::with(['permissions' => function($query) {
            $query->where('parent_id', 0)->orderBy('id');
        }])->orderBy('sort_order')->get();

        return response()->json([
            'status' => true,
            'message' => 'Menus with permissions retrieved successfully',
            'data' => $menus
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:admin_permissions',
            'description_ar' => 'required|string|max:255',
            'description_en' => 'required|string|max:255',
            'admin_menu_id' => 'nullable|exists:admin_menus,id',
            'admin_sub_menu_id' => 'nullable|exists:admin_sub_menus,id',
            'parent_id' => 'nullable|exists:admin_permissions,id',
            'is_parent' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $permission = AdminPermission::create([
                'title' => $request->title,
                'description_ar' => $request->description_ar,
                'description_en' => $request->description_en,
                'admin_menu_id' => $request->admin_menu_id,
                'admin_sub_menu_id' => $request->admin_sub_menu_id,
                'parent_id' => $request->parent_id ?? 0,
                'is_parent' => $request->is_parent ?? false
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Permission created successfully',
                'data' => $permission
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $permission = AdminPermission::with(['menu', 'subMenu'])->find($id);

        if (!$permission) {
            return response()->json([
                'status' => false,
                'message' => 'Permission not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $permission
        ]);
    }

    public function update(Request $request, $id)
    {
        $permission = AdminPermission::find($id);

        if (!$permission) {
            return response()->json([
                'status' => false,
                'message' => 'Permission not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:admin_permissions,title,' . $id,
            'description_ar' => 'required|string|max:255',
            'description_en' => 'required|string|max:255',
            'admin_menu_id' => 'nullable|exists:admin_menus,id',
            'admin_sub_menu_id' => 'nullable|exists:admin_sub_menus,id',
            'parent_id' => 'nullable|exists:admin_permissions,id',
            'is_parent' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $permission->update([
                'title' => $request->title,
                'description_ar' => $request->description_ar,
                'description_en' => $request->description_en,
                'admin_menu_id' => $request->admin_menu_id,
                'admin_sub_menu_id' => $request->admin_sub_menu_id,
                'parent_id' => $request->parent_id ?? 0,
                'is_parent' => $request->is_parent ?? false
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Permission updated successfully',
                'data' => $permission
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $permission = AdminPermission::find($id);

        if (!$permission) {
            return response()->json([
                'status' => false,
                'message' => 'Permission not found'
            ], 404);
        }

        try {
            // Check if this permission has children
            if ($permission->is_parent && AdminPermission::where('parent_id', $id)->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot delete parent permission that has children'
                ], 400);
            }

            $permission->delete();

            return response()->json([
                'status' => true,
                'message' => 'Permission deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete permission',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
