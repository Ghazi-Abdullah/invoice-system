<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminGroup;
use App\Models\AdminPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminGroupController extends Controller
{
    public function index()
    {
        $groups = AdminGroup::withCount(['users', 'permissions'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Groups retrieved successfully',
            'data' => $groups
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $group = AdminGroup::create($request->only(['title_ar', 'title_en', 'description']));

            return response()->json([
                'status' => true,
                'message' => 'Group created successfully',
                'data' => $group
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $group = AdminGroup::with(['permissions', 'users'])->find($id);

        if (!$group) {
            return response()->json([
                'status' => false,
                'message' => 'Group not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $group
        ]);
    }

    public function update(Request $request, $id)
    {
        $group = AdminGroup::find($id);

        if (!$group) {
            return response()->json([
                'status' => false,
                'message' => 'Group not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $group->update($request->only(['title_ar', 'title_en', 'description']));

            return response()->json([
                'status' => true,
                'message' => 'Group updated successfully',
                'data' => $group
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $group = AdminGroup::find($id);

        if (!$group) {
            return response()->json([
                'status' => false,
                'message' => 'Group not found'
            ], 404);
        }

        if ($group->id === 1) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete admin group'
            ], 403);
        }

        try {
            // تأكد من عدم وجود مستخدمين في المجموعة
            if ($group->users()->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot delete group with users'
                ], 400);
            }

            $group->delete();

            return response()->json([
                'status' => true,
                'message' => 'Group deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAvailablePermissions($groupId)
{
    $group = AdminGroup::with('permissions')->find($groupId);

    if (!$group) {
        return response()->json([
            'status' => false,
            'message' => 'Group not found'
        ], 404);
    }

    // جلب جميع الصلاحيات
    $permissions = AdminPermission::with('menu')->get();

    // استخراج معرفات الصلاحيات المعينة للمجموعة
    $selectedPermissions = $group->permissions->pluck('id')->toArray();

    return response()->json([
        'status' => true,
        'data' => $permissions,
        'selected_permissions' => $selectedPermissions
    ]);
}

    public function updatePermissions(Request $request, $groupId)
{
    $validator = Validator::make($request->all(), [
        'permissions' => 'required|array',
        'permissions.*' => 'exists:admin_permissions,id'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }

    $group = AdminGroup::find($groupId);

    if (!$group) {
        return response()->json([
            'status' => false,
            'message' => 'Group not found'
        ], 404);
    }

    try {
        DB::beginTransaction();

        // حذف جميع الصلاحيات الحالية
        DB::table('admin_group_permissions')->where('admin_group_id', $groupId)->delete();

        // إضافة الصلاحيات الجديدة
        foreach ($request->permissions as $permissionId) {
            DB::table('admin_group_permissions')->insert([
                'admin_group_id' => $groupId,
                'admin_permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        DB::commit();

        // جلب المجموعة مع الصلاحيات المحدثة
        $group->load('permissions');

        return response()->json([
            'status' => true,
            'message' => 'Permissions updated successfully',
            'data' => $group
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => false,
            'message' => 'Failed to update permissions',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
