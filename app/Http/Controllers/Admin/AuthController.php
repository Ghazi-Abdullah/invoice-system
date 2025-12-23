<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminPermission;
use App\Constants\Constants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ResponseTrait;

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->failureResponse($validator->errors()->first(), $validator->errors());
            }

            // Find user by email
            $user = User::with(['adminGroup.permissions'])->where('email', $request->email)->first();

            // Check if user exists
            if (!$user) {
                return $this->failureResponse('User not found with this email', null, 401);
            }

            // Check if password is correct
            if (!Hash::check($request->password, $user->password)) {
                return $this->failureResponse('Incorrect password', null, 401);
            }

            // Check if user is active
            if (!$user->is_active) {
                return $this->failureResponse('Account is inactive. Please contact administrator.', null, 403);
            }

            // Get user permissions
            $permissions = $this->getUserPermissions($user);
            $is_admin = $user->admin_group_id == Constants::SUPER_ADMIN_GROUP_ID;

            // Create token
            $token = $user->createToken('invoice-system-token')->plainTextToken;

            // Return response
            return $this->successResponse('Login successful', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'company_name' => $user->company_name,
                    'admin_group_id' => $user->admin_group_id,
                    'is_active' => $user->is_active,
                    'adminGroup' => $user->adminGroup,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'permissions' => $permissions,
                'is_admin' => $is_admin
            ]);

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage(), [
                'email' => $request->email,
                'ip' => $request->ip()
            ]);
            return $this->failureResponse('Login failed: ' . $e->getMessage(), null, 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            // Revoke the token that was used to authenticate the current request
            $request->user()->currentAccessToken()->delete();

            return $this->successResponse('Logout successful', null);

        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return $this->failureResponse('Logout failed: ' . $e->getMessage(), null, 500);
        }
    }

    public function me(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->failureResponse('User not authenticated', null, 401);
            }

            // Load relationships
            $user->load(['adminGroup.permissions']);

            // Get permissions
            $permissions = $this->getUserPermissions($user);
            $is_admin = $user->admin_group_id == Constants::SUPER_ADMIN_GROUP_ID;

            return $this->successResponse('User retrieved successfully', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'company_name' => $user->company_name,
                    'admin_group_id' => $user->admin_group_id,
                    'is_active' => $user->is_active,
                    'adminGroup' => $user->adminGroup,
                ],
                'permissions' => $permissions,
                'is_admin' => $is_admin
            ]);

        } catch (\Exception $e) {
            Log::error('Me endpoint error: ' . $e->getMessage());
            return $this->failureResponse('Failed to retrieve user: ' . $e->getMessage(), null, 500);
        }
    }

    public function refresh(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->failureResponse('User not authenticated', null, 401);
            }

            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            // Create new token
            $token = $user->createToken('invoice-system-token')->plainTextToken;

            return $this->successResponse('Token refreshed successfully', [
                'token' => $token,
                'token_type' => 'Bearer'
            ]);

        } catch (\Exception $e) {
            Log::error('Refresh token error: ' . $e->getMessage());
            return $this->failureResponse('Token refresh failed: ' . $e->getMessage(), null, 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email'
            ]);

            if ($validator->fails()) {
                return $this->failureResponse('Validation failed', $validator->errors(), 422);
            }

            // TODO: Implement password reset logic

            return $this->successResponse('Password reset instructions sent to your email', null);

        } catch (\Exception $e) {
            Log::error('Forgot password error: ' . $e->getMessage());
            return $this->failureResponse('Password reset failed: ' . $e->getMessage(), null, 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'password' => 'required|string|min:8|confirmed',
                'token' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->failureResponse('Validation failed', $validator->errors(), 422);
            }

            // TODO: Implement password reset logic

            return $this->successResponse('Password reset successfully', null);

        } catch (\Exception $e) {
            Log::error('Reset password error: ' . $e->getMessage());
            return $this->failureResponse('Password reset failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get user permissions based on their group
     */
    private function getUserPermissions(User $user)
    {
        try {
            // If user is Super Admin (group_id = 1), get ALL permissions
            if ($user->admin_group_id == Constants::SUPER_ADMIN_GROUP_ID) {
                return AdminPermission::where('is_active', true)
                    ->pluck('title')
                    ->toArray();
            }

            // For other users, get permissions from their admin group
            if ($user->adminGroup && $user->adminGroup->permissions) {
                return $user->adminGroup->permissions
                    ->where('is_active', true)
                    ->pluck('title')
                    ->toArray();
            }

            return [];

        } catch (\Exception $e) {
            Log::error('Get user permissions error: ' . $e->getMessage());
            return [];
        }
    }
}
