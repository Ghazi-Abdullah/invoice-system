<?php

namespace App\Repository\Admin\AdminGroup;

use App\Models\AdminGroup;
use App\Models\AdminPermission;
use App\Models\ActivityLog;
use App\Constants\Constants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminGroupRepository implements AdminGroupInterface
{
    public function index($request)
    {
        try {
            $query = AdminGroup::withCount(['users', 'permissions']);

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title_en', 'like', "%{$search}%")
                      ->orWhere('title_ar', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply status filter
            if ($request->has('is_active') && $request->is_active !== '') {
                $query->where('is_active', (bool)$request->is_active);
            }

            // Exclude system groups if needed
            if ($request->has('exclude_system') && $request->exclude_system) {
                $query->where('is_system', false);
            }

            // Get paginated results
            $perPage = $request->per_page ?? Constants::DEFAULT_PER_PAGE;
            $perPage = min($perPage, Constants::MAX_PER_PAGE);
            $perPage = max($perPage, Constants::MIN_PER_PAGE);

            $adminGroups = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return [
                'status' => true,
                'message' => 'Admin groups retrieved successfully',
                'data' => $adminGroups
            ];

        } catch (\Exception $e) {
            Log::error('AdminGroupRepository index error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'status' => false,
                'message' => 'Failed to retrieve admin groups: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function show($id)
    {
        try {
            $adminGroup = AdminGroup::with(['permissions', 'users'])->withCount(['users', 'permissions'])->find($id);

            if (!$adminGroup) {
                return [
                    'status' => false,
                    'message' => 'Admin group not found',
                    'data' => null
                ];
            }

            return [
                'status' => true,
                'message' => 'Admin group retrieved successfully',
                'data' => $adminGroup
            ];

        } catch (\Exception $e) {
            Log::error('AdminGroupRepository show error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to retrieve admin group: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function store($request)
    {
        DB::beginTransaction();

        try {
            // Check if title_en is unique
            $existingGroup = AdminGroup::where('title_en', $request->title_en)->first();
            if ($existingGroup) {
                return [
                    'status' => false,
                    'message' => 'Admin group with this English title already exists',
                    'data' => null
                ];
            }

            // Create admin group
            $adminGroup = AdminGroup::create([
                'title_en' => $request->title_en,
                'title_ar' => $request->title_ar,
                'description' => $request->description ?? null,
                'is_active' => $request->is_active ?? true,
                'is_system' => false,
            ]);

            // Attach permissions if provided
            if ($request->has('permissions') && is_array($request->permissions)) {
                $validPermissions = AdminPermission::whereIn('id', $request->permissions)
                    ->where('is_active', true)
                    ->pluck('id')
                    ->toArray();

                $adminGroup->permissions()->attach($validPermissions);
            }

            // Log activity
            ActivityLog::log(
                'CREATE',
                'Created admin group: ' . $adminGroup->title_en,
                $adminGroup
            );

            DB::commit();

            return [
                'status' => true,
                'message' => 'Admin group created successfully',
                'data' => $adminGroup->load(['permissions'])
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdminGroupRepository store error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'status' => false,
                'message' => 'Failed to create admin group: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function update($request, $adminGroup)
    {
        DB::beginTransaction();

        try {
            $oldValues = $adminGroup->toArray();

            // Check if title_en is unique (excluding current group)
            if ($request->has('title_en') && $request->title_en !== $adminGroup->title_en) {
                $existingGroup = AdminGroup::where('title_en', $request->title_en)
                    ->where('id', '!=', $adminGroup->id)
                    ->first();

                if ($existingGroup) {
                    return [
                        'status' => false,
                        'message' => 'Admin group with this English title already exists',
                        'data' => null
                    ];
                }
            }

            // Update admin group
            $updateData = [
                'title_en' => $request->title_en ?? $adminGroup->title_en,
                'title_ar' => $request->title_ar ?? $adminGroup->title_ar,
                'description' => $request->description ?? $adminGroup->description,
                'is_active' => $request->has('is_active') ? (bool)$request->is_active : $adminGroup->is_active
            ];

            $adminGroup->update($updateData);

            // Update permissions if provided
            if ($request->has('permissions') && is_array($request->permissions)) {
                $validPermissions = AdminPermission::whereIn('id', $request->permissions)
                    ->where('is_active', true)
                    ->pluck('id')
                    ->toArray();

                $adminGroup->permissions()->sync($validPermissions);
            }

            // Log activity
            ActivityLog::log(
                'UPDATE',
                'Updated admin group: ' . $adminGroup->title_en,
                $adminGroup,
                $oldValues,
                $adminGroup->fresh()->toArray()
            );

            DB::commit();

            return [
                'status' => true,
                'message' => 'Admin group updated successfully',
                'data' => $adminGroup->load(['permissions'])
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdminGroupRepository update error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to update admin group: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function destroy($adminGroup)
    {
        DB::beginTransaction();

        try {
            $groupId = $adminGroup->id;
            $groupName = $adminGroup->title_en;

            // Check if it's a system group
            if ($adminGroup->is_system) {
                return [
                    'status' => false,
                    'message' => 'Cannot delete system admin group',
                    'data' => null
                ];
            }

            // Check if any users are assigned to this group
            if ($adminGroup->users()->count() > 0) {
                return [
                    'status' => false,
                    'message' => 'Cannot delete admin group with assigned users',
                    'data' => null
                ];
            }

            // Log activity before deletion
            ActivityLog::log(
                'DELETE',
                'Deleted admin group: ' . $groupName,
                $adminGroup
            );

            // Detach all permissions
            $adminGroup->permissions()->detach();

            // Delete the admin group
            $adminGroup->delete();

            DB::commit();

            return [
                'status' => true,
                'message' => 'Admin group deleted successfully',
                'data' => ['id' => $groupId]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdminGroupRepository destroy error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to delete admin group: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getPermissions($adminGroup)
    {
        try {
            $permissions = $adminGroup->permissions()->get();

            return [
                'status' => true,
                'message' => 'Permissions retrieved successfully',
                'data' => $permissions
            ];

        } catch (\Exception $e) {
            Log::error('AdminGroupRepository getPermissions error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to retrieve permissions: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function updatePermissions($adminGroup, $permissions)
    {
        DB::beginTransaction();

        try {
            // Validate permissions exist
            $validPermissions = AdminPermission::whereIn('id', $permissions)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();

            if (count($validPermissions) !== count($permissions)) {
                return [
                    'status' => false,
                    'message' => 'Some permissions are invalid',
                    'data' => null
                ];
            }

            // Sync permissions
            $adminGroup->permissions()->sync($validPermissions);

            // Log activity
            ActivityLog::log(
                'UPDATE',
                'Updated permissions for admin group: ' . $adminGroup->title_en,
                $adminGroup
            );

            DB::commit();

            return [
                'status' => true,
                'message' => 'Permissions updated successfully',
                'data' => $adminGroup->load('permissions')
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdminGroupRepository updatePermissions error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to update permissions: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getAvailablePermissions()
    {
        try {
            $permissions = AdminPermission::where('is_active', true)
                ->orderBy('title')
                ->get(['id', 'title', 'description_en', 'description_ar']);

            return [
                'status' => true,
                'message' => 'Available permissions retrieved successfully',
                'data' => $permissions
            ];

        } catch (\Exception $e) {
            Log::error('AdminGroupRepository getAvailablePermissions error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to retrieve available permissions: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getGroupsWithPermissions()
    {
        try {
            $groups = AdminGroup::with(['permissions' => function($query) {
                $query->select('id', 'title', 'description_en', 'description_ar');
            }])
            ->orderBy('created_at', 'desc')
            ->get(['id', 'title_en', 'title_ar', 'description', 'is_active', 'is_system']);

            return [
                'status' => true,
                'message' => 'Groups with permissions retrieved successfully',
                'data' => $groups
            ];

        } catch (\Exception $e) {
            Log::error('AdminGroupRepository getGroupsWithPermissions error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to retrieve groups with permissions: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getSimpleList()
    {
        try {
            $groups = AdminGroup::where('is_active', true)
                ->orderBy('title_en')
                ->get(['id', 'title_en', 'title_ar']);

            return [
                'status' => true,
                'message' => 'Admin groups list retrieved successfully',
                'data' => $groups
            ];

        } catch (\Exception $e) {
            Log::error('AdminGroupRepository getSimpleList error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to retrieve admin groups list: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
