<?php

namespace App\Repository\Admin\Permission;

use App\Models\AdminPermission;
use App\Models\ActivityLog;
use App\Constants\Constants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PermissionRepository implements PermissionInterface
{
    public function index($request)
    {
        try {
            $query = AdminPermission::query();

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description_en', 'like', "%{$search}%")
                      ->orWhere('description_ar', 'like', "%{$search}%");
                });
            }

            // Apply status filter
            if ($request->has('is_active') && $request->is_active !== '') {
                $query->where('is_active', (bool)$request->is_active);
            }

            // Get paginated results
            $perPage = $request->per_page ?? Constants::DEFAULT_PER_PAGE;
            $perPage = min($perPage, Constants::MAX_PER_PAGE);
            $perPage = max($perPage, Constants::MIN_PER_PAGE);

            $permissions = $query->orderBy('title')->paginate($perPage);

            return [
                'status' => true,
                'message' => 'Permissions retrieved successfully',
                'data' => $permissions
            ];

        } catch (\Exception $e) {
            Log::error('PermissionRepository index error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to retrieve permissions: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function show($id)
    {
        try {
            $permission = AdminPermission::find($id);

            if (!$permission) {
                return [
                    'status' => false,
                    'message' => 'Permission not found',
                    'data' => null
                ];
            }

            return [
                'status' => true,
                'message' => 'Permission retrieved successfully',
                'data' => $permission
            ];

        } catch (\Exception $e) {
            Log::error('PermissionRepository show error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to retrieve permission: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function store($request)
    {
        DB::beginTransaction();

        try {
            // Check if permission title is unique
            $existingPermission = AdminPermission::where('title', $request->title)->first();
            if ($existingPermission) {
                return [
                    'status' => false,
                    'message' => 'Permission with this title already exists',
                    'data' => null
                ];
            }

            // Create permission
            $permission = AdminPermission::create([
                'title' => $request->title,
                'description_en' => $request->description_en ?? null,
                'description_ar' => $request->description_ar ?? null,
                'is_active' => $request->is_active ?? true,
                'created_by' => auth()->id() ?? 1
            ]);

            // Log activity
            ActivityLog::log(
                'CREATE',
                'Created permission: ' . $permission->title,
                $permission
            );

            DB::commit();

            return [
                'status' => true,
                'message' => 'Permission created successfully',
                'data' => $permission
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PermissionRepository store error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to create permission: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function update($request, $permission)
    {
        DB::beginTransaction();

        try {
            $oldValues = $permission->toArray();

            // Check if permission title is unique (excluding current permission)
            if ($request->has('title') && $request->title !== $permission->title) {
                $existingPermission = AdminPermission::where('title', $request->title)
                    ->where('id', '!=', $permission->id)
                    ->first();

                if ($existingPermission) {
                    return [
                        'status' => false,
                        'message' => 'Permission with this title already exists',
                        'data' => null
                    ];
                }
            }

            // Update permission
            $updateData = [
                'title' => $request->title ?? $permission->title,
                'description_en' => $request->description_en ?? $permission->description_en,
                'description_ar' => $request->description_ar ?? $permission->description_ar,
                'is_active' => $request->has('is_active') ? (bool)$request->is_active : $permission->is_active
            ];

            $permission->update($updateData);

            // Log activity
            ActivityLog::log(
                'UPDATE',
                'Updated permission: ' . $permission->title,
                $permission,
                $oldValues,
                $permission->fresh()->toArray()
            );

            DB::commit();

            return [
                'status' => true,
                'message' => 'Permission updated successfully',
                'data' => $permission
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PermissionRepository update error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to update permission: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function destroy($permission)
    {
        DB::beginTransaction();

        try {
            $permissionId = $permission->id;
            $permissionTitle = $permission->title;

            // Check if permission is assigned to any groups
            if ($permission->groups()->count() > 0) {
                return [
                    'status' => false,
                    'message' => 'Cannot delete permission that is assigned to groups',
                    'data' => null
                ];
            }

            // Log activity before deletion
            ActivityLog::log(
                'DELETE',
                'Deleted permission: ' . $permissionTitle,
                $permission
            );

            // Delete the permission
            $permission->delete();

            DB::commit();

            return [
                'status' => true,
                'message' => 'Permission deleted successfully',
                'data' => ['id' => $permissionId]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PermissionRepository destroy error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to delete permission: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getAllPermissions()
    {
        try {
            $permissions = AdminPermission::where('is_active', true)
                ->orderBy('title')
                ->get(['id', 'title', 'description_en', 'description_ar']);

            return [
                'status' => true,
                'message' => 'All permissions retrieved successfully',
                'data' => $permissions
            ];

        } catch (\Exception $e) {
            Log::error('PermissionRepository getAllPermissions error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to retrieve all permissions: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
