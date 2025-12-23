<?php

namespace App\Repository\Admin\User;

use App\Models\User;
use App\Models\ActivityLog;
use App\Constants\Constants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserInterface
{
    public function index($request)
    {
        try {
            $query = User::with('adminGroup')
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            if ($request->has('admin_group_id')) {
                $query->where('admin_group_id', $request->admin_group_id);
            }

            if ($request->has('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%");
                });
            }

            // Get paginated or all results
            if ($request->has('per_page')) {
                $users = $query->paginate(
                    min($request->per_page, Constants::MAX_PER_PAGE)
                );
            } else {
                $users = $query->get();
            }

            return [
                'status' => true,
                'message' => 'Users retrieved successfully',
                'data' => $users
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to retrieve users: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function show($id)
    {
        try {
            $user = User::with(['adminGroup'])->find($id);

            if (!$user) {
                return [
                    'status' => false,
                    'message' => 'User not found',
                    'data' => null
                ];
            }

            return [
                'status' => true,
                'message' => 'User retrieved successfully',
                'data' => $user
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to retrieve user: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function store($request)
    {
        DB::beginTransaction();

        try {
            // Check if email already exists
            if (User::where('email', $request->email)->exists()) {
                return [
                    'status' => false,
                    'message' => 'Email already exists',
                    'data' => null
                ];
            }

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'address' => $request->address,
                'company_name' => $request->company_name,
                'tax_number' => $request->tax_number,
                'is_active' => $request->is_active ?? true,
                'admin_group_id' => $request->admin_group_id
            ]);

            // Log activity
            ActivityLog::log(
                'CREATE',
                'Created user: ' . $user->name,
                $user
            );

            DB::commit();

            return [
                'status' => true,
                'message' => 'User created successfully',
                'data' => $user
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => false,
                'message' => 'Failed to create user: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function update($request, $user)
    {
        DB::beginTransaction();

        try {
            $oldValues = $user->toArray();

            // Check if email already exists for other users
            if ($request->has('email') && $request->email !== $user->email) {
                if (User::where('email', $request->email)->where('id', '!=', $user->id)->exists()) {
                    return [
                        'status' => false,
                        'message' => 'Email already exists',
                        'data' => null
                    ];
                }
            }

            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'company_name' => $request->company_name,
                'tax_number' => $request->tax_number,
                'is_active' => $request->is_active ?? $user->is_active,
                'admin_group_id' => $request->admin_group_id ?? $user->admin_group_id
            ];

            // Update password if provided
            if ($request->has('password') && $request->password) {
                $updateData['password'] = Hash::make($request->password);
            }

            // Update user
            $user->update($updateData);

            // Log activity
            ActivityLog::log(
                'UPDATE',
                'Updated user: ' . $user->name,
                $user,
                $oldValues,
                $user->fresh()->toArray()
            );

            DB::commit();

            return [
                'status' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => false,
                'message' => 'Failed to update user: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function destroy($user)
    {
        DB::beginTransaction();

        try {
            // Prevent deleting self
            if ($user->id === auth()->id()) {
                return [
                    'status' => false,
                    'message' => 'Cannot delete your own account',
                    'data' => null
                ];
            }

            // Prevent deleting super admin
            if ($user->isSuperAdmin()) {
                return [
                    'status' => false,
                    'message' => 'Cannot delete super admin',
                    'data' => null
                ];
            }

            $userName = $user->name;
            $userId = $user->id;

            // Check if user has created invoices
            if ($user->createdInvoices()->count() > 0) {
                return [
                    'status' => false,
                    'message' => 'Cannot delete user with created invoices',
                    'data' => null
                ];
            }

            // Log activity before deletion
            ActivityLog::log(
                'DELETE',
                'Deleted user: ' . $userName,
                $user
            );

            // Delete user
            $user->delete();

            DB::commit();

            return [
                'status' => true,
                'message' => 'User deleted successfully',
                'data' => ['id' => $userId]
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function updateProfile($request)
    {
        try {
            $user = auth()->user();
            $oldValues = $user->toArray();

            $updateData = [
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
                'company_name' => $request->company_name,
                'tax_number' => $request->tax_number
            ];

            // Update email if provided and different
            if ($request->has('email') && $request->email !== $user->email) {
                // Check if email already exists
                if (User::where('email', $request->email)->where('id', '!=', $user->id)->exists()) {
                    return [
                        'status' => false,
                        'message' => 'Email already exists',
                        'data' => null
                    ];
                }
                $updateData['email'] = $request->email;
            }

            $user->update($updateData);

            // Log activity
            ActivityLog::log(
                'UPDATE_PROFILE',
                'Updated profile',
                $user,
                $oldValues,
                $user->fresh()->toArray()
            );

            return [
                'status' => true,
                'message' => 'Profile updated successfully',
                'data' => $user
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function changePassword($request)
    {
        try {
            $user = auth()->user();

            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return [
                    'status' => false,
                    'message' => 'Current password is incorrect',
                    'data' => null
                ];
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            // Log activity
            ActivityLog::log(
                'CHANGE_PASSWORD',
                'Changed password',
                $user
            );

            return [
                'status' => true,
                'message' => 'Password changed successfully',
                'data' => null
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to change password: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function updateStatus($user, $status)
    {
        try {
            // Prevent deactivating self
            if ($user->id === auth()->id() && !$status) {
                return [
                    'status' => false,
                    'message' => 'Cannot deactivate your own account',
                    'data' => null
                ];
            }

            $oldStatus = $user->is_active;
            $user->update(['is_active' => $status]);

            // Log activity
            ActivityLog::log(
                'UPDATE_STATUS',
                'Updated user status from ' . ($oldStatus ? 'Active' : 'Inactive') . ' to ' . ($status ? 'Active' : 'Inactive'),
                $user
            );

            return [
                'status' => true,
                'message' => 'User status updated successfully',
                'data' => $user
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to update user status: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getStaffUsers()
    {
        try {
            $staff = User::with(['adminGroup'])
                ->staff()
                ->active()
                ->get();

            return [
                'status' => true,
                'message' => 'Staff users retrieved successfully',
                'data' => $staff
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to retrieve staff users: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getClientUsers()
    {
        try {
            $clients = User::with(['adminGroup'])
                ->clients()
                ->active()
                ->get();

            return [
                'status' => true,
                'message' => 'Client users retrieved successfully',
                'data' => $clients
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to retrieve client users: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
