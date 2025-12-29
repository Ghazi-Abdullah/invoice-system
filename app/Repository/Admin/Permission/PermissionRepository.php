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
            Log::info('Starting permission index method');

            $query = AdminPermission::with(['menu', 'subMenu', 'parent']);

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

            Log::info('Before pagination', ['perPage' => $perPage]);

            $permissions = $query->orderBy('admin_menu_id')
                ->orderBy('admin_sub_menu_id')
                ->orderBy('parent_id')
                ->orderBy('title')
                ->paginate($perPage);

            Log::info('Permissions retrieved', ['count' => $permissions->count()]);

            return [
                'status' => true,
                'message' => __('messages.permissions_fetched'),
                'data' => $permissions
            ];

        } catch (\Exception $e) {
            Log::error('PermissionRepository index error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'status' => false,
                'message' => __('messages.error') . ': ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function show($id)
    {
        try {
            $permission = AdminPermission::with(['menu', 'subMenu', 'parent', 'children'])->find($id);

            if (!$permission) {
                return [
                    'status' => false,
                    'message' => __('messages.permission_not_found'),
                    'data' => null
                ];
            }

            return [
                'status' => true,
                'message' => __('messages.permission_fetched'),
                'data' => $permission
            ];

        } catch (\Exception $e) {
            Log::error('PermissionRepository show error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => __('messages.error') . ': ' . $e->getMessage(),
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
                    'message' => __('messages.unique', ['attribute' => __('validation.attributes.title')]),
                    'data' => null
                ];
            }

            // Create permission
            $permission = AdminPermission::create([
                'title' => $request->title,
                'description_en' => $request->description_en ?? null,
                'description_ar' => $request->description_ar ?? null,
                'admin_menu_id' => $request->admin_menu_id ?? null,
                'admin_sub_menu_id' => $request->admin_sub_menu_id ?? null,
                'parent_id' => $request->parent_id ?? null,
                'is_parent' => $request->is_parent ?? false,
                'is_active' => $request->is_active ?? true,
            ]);

            // Log activity
            ActivityLog::log(
                Constants::ACTIVITY_CREATE,
                __('messages.permission_created_log', ['title' => $permission->title]),
                $permission
            );

            DB::commit();

            return [
                'status' => true,
                'message' => __('messages.permission_created'),
                'data' => $permission
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PermissionRepository store error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => __('messages.operation_failed') . ': ' . $e->getMessage(),
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
                        'message' => __('messages.unique', ['attribute' => __('validation.attributes.title')]),
                        'data' => null
                    ];
                }
            }

            // Update permission
            $updateData = [
                'title' => $request->title ?? $permission->title,
                'description_en' => $request->description_en ?? $permission->description_en,
                'description_ar' => $request->description_ar ?? $permission->description_ar,
                'admin_menu_id' => $request->admin_menu_id ?? $permission->admin_menu_id,
                'admin_sub_menu_id' => $request->admin_sub_menu_id ?? $permission->admin_sub_menu_id,
                'parent_id' => $request->parent_id ?? $permission->parent_id,
                'is_parent' => $request->has('is_parent') ? (bool)$request->is_parent : $permission->is_parent,
                'is_active' => $request->has('is_active') ? (bool)$request->is_active : $permission->is_active
            ];

            $permission->update($updateData);

            // Log activity
            ActivityLog::log(
                Constants::ACTIVITY_UPDATE,
                __('messages.permission_updated_log', ['title' => $permission->title]),
                $permission,
                $oldValues,
                $permission->fresh()->toArray()
            );

            DB::commit();

            return [
                'status' => true,
                'message' => __('messages.permission_updated'),
                'data' => $permission
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PermissionRepository update error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => __('messages.operation_failed') . ': ' . $e->getMessage(),
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
                    'message' => __('messages.cannot_delete_permission_with_groups'),
                    'data' => null
                ];
            }

            // Check if permission has children
            if ($permission->children()->count() > 0) {
                return [
                    'status' => false,
                    'message' => __('messages.cannot_delete_permission_with_children'),
                    'data' => null
                ];
            }

            // Log activity before deletion
            ActivityLog::log(
                Constants::ACTIVITY_DELETE,
                __('messages.permission_deleted_log', ['title' => $permissionTitle]),
                $permission
            );

            // Delete the permission
            $permission->delete();

            DB::commit();

            return [
                'status' => true,
                'message' => __('messages.permission_deleted'),
                'data' => ['id' => $permissionId]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PermissionRepository destroy error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => __('messages.operation_failed') . ': ' . $e->getMessage(),
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
                'message' => __('messages.permissions_fetched'),
                'data' => $permissions
            ];

        } catch (\Exception $e) {
            Log::error('PermissionRepository getAllPermissions error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => __('messages.error') . ': ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getPermissionsWithMenus()
    {
        try {
            $permissions = AdminPermission::with(['menu', 'subMenu', 'parent'])
                ->where('is_active', true)
                ->orderBy('admin_menu_id')
                ->orderBy('admin_sub_menu_id')
                ->orderBy('parent_id')
                ->orderBy('id')
                ->get();

            // تحويل البيانات لتجنب المشاكل مع القيم null
            $formattedPermissions = $permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'title' => $permission->title,
                    'description_en' => $permission->description_en,
                    'description_ar' => $permission->description_ar,
                    'is_parent' => $permission->is_parent,
                    'is_active' => $permission->is_active,
                    'admin_menu_id' => $permission->admin_menu_id,
                    'admin_sub_menu_id' => $permission->admin_sub_menu_id,
                    'parent_id' => $permission->parent_id,
                    'menu' => $permission->menu ? [
                        'id' => $permission->menu->id,
                        'title_en' => $permission->menu->title_en,
                        'title_ar' => $permission->menu->title_ar,
                    ] : null,
                    'sub_menu' => $permission->subMenu ? [
                        'id' => $permission->subMenu->id,
                        'title_en' => $permission->subMenu->title_en,
                        'title_ar' => $permission->subMenu->title_ar,
                    ] : null,
                    'parent_permission' => $permission->parent ? [
                        'id' => $permission->parent->id,
                        'title' => $permission->parent->title,
                    ] : null,
                ];
            });

            return [
                'status' => true,
                'message' => __('messages.permissions_with_menus_fetched'),
                'data' => $formattedPermissions
            ];

        } catch (\Exception $e) {
            Log::error('PermissionRepository getPermissionsWithMenus error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'status' => false,
                'message' => __('messages.error') . ': ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getParentPermissions()
    {
        try {
            $permissions = AdminPermission::where('is_parent', true)
                ->where('is_active', true)
                ->orderBy('title')
                ->get(['id', 'title', 'description_en', 'description_ar']);

            return [
                'status' => true,
                'message' => __('messages.parent_permissions_fetched'),
                'data' => $permissions
            ];

        } catch (\Exception $e) {
            Log::error('PermissionRepository getParentPermissions error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => __('messages.error') . ': ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
