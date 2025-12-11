<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminPermission;
use App\Models\AdminMenu;
use Illuminate\Http\Request;

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
        $menus = AdminMenu::with(['subMenus' => function($query) {
            $query->with(['permissions' => function($q) {
                $q->where('parent_id', 0);
            }]);
        }])->orderBy('sort_order')->get();

        return response()->json([
            'status' => true,
            'message' => 'Menus with permissions retrieved successfully',
            'data' => $menus
        ]);
    }

    public function getUserPermissions(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $permissions = $user->permissions;

        return response()->json([
            'status' => true,
            'message' => 'User permissions retrieved successfully',
            'data' => $permissions
        ]);
    }
}
