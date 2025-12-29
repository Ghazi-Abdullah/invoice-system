<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminPermission;
use App\Constants\Constants;
use Illuminate\Http\Request;
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
                return $this->failureResponse(
                    __('messages.validation_error'),
                    $validator->errors(),
                    Constants::RESPONSE_VALIDATION_ERROR
                );
            }

            $user = User::with(['adminGroup.permissions'])->where('email', $request->email)->first();

            if (!$user) {
                return $this->failureResponse(
                    __('messages.user_not_found'),
                    null,
                    Constants::RESPONSE_UNAUTHORIZED
                );
            }

            if (!Hash::check($request->password, $user->password)) {
                return $this->failureResponse(
                    __('messages.incorrect_password'),
                    null,
                    Constants::RESPONSE_UNAUTHORIZED
                );
            }

            if (!$user->is_active) {
                return $this->failureResponse(
                    __('messages.inactive_account'),
                    null,
                    Constants::RESPONSE_FORBIDDEN
                );
            }

            $permissions = $this->getUserPermissions($user);
            $is_admin = $user->admin_group_id == Constants::SUPER_ADMIN_GROUP_ID;

            $token = $user->createToken('invoice-system-token')->plainTextToken;

            return $this->successResponse(
                __('messages.login_success'),
                [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'company_name' => $user->company_name,
                        'admin_group_id' => $user->admin_group_id,
                        'is_active' => $user->is_active,
                        'is_admin' => $is_admin,
                        'adminGroup' => $user->adminGroup,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'permissions' => $permissions,
                    'is_admin' => $is_admin
                ]
            );

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage(), [
                'email' => $request->email,
                'ip' => $request->ip()
            ]);
            return $this->failureResponse(
                __('messages.login_failed') . ': ' . $e->getMessage(),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return $this->successResponse(__('messages.logout_success'), null);

        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.logout_success') . ': ' . $e->getMessage(),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function me(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->failureResponse(
                    __('messages.unauthenticated'),
                    null,
                    Constants::RESPONSE_UNAUTHORIZED
                );
            }

            $user->load(['adminGroup.permissions']);

            $permissions = $this->getUserPermissions($user);
            $is_admin = $user->admin_group_id == Constants::SUPER_ADMIN_GROUP_ID;

            return $this->successResponse(
                __('messages.user_fetched'),
                [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'company_name' => $user->company_name,
                        'admin_group_id' => $user->admin_group_id,
                        'is_active' => $user->is_active,
                        'is_admin' => $is_admin,
                        'adminGroup' => $user->adminGroup,
                    ],
                    'permissions' => $permissions,
                    'is_admin' => $is_admin
                ]
            );

        } catch (\Exception $e) {
            Log::error('Me endpoint error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.error') . ': ' . $e->getMessage(),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function refresh(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->failureResponse(
                    __('messages.unauthenticated'),
                    null,
                    Constants::RESPONSE_UNAUTHORIZED
                );
            }

            $request->user()->currentAccessToken()->delete();

            $token = $user->createToken('invoice-system-token')->plainTextToken;

            return $this->successResponse(
                __('messages.token_refreshed'),
                [
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            );

        } catch (\Exception $e) {
            Log::error('Refresh token error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.error') . ': ' . $e->getMessage(),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email'
            ]);

            if ($validator->fails()) {
                return $this->failureResponse(
                    __('messages.validation_error'),
                    $validator->errors(),
                    Constants::RESPONSE_VALIDATION_ERROR
                );
            }

            return $this->successResponse(__('messages.success'), null);

        } catch (\Exception $e) {
            Log::error('Forgot password error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.error') . ': ' . $e->getMessage(),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
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
                return $this->failureResponse(
                    __('messages.validation_error'),
                    $validator->errors(),
                    Constants::RESPONSE_VALIDATION_ERROR
                );
            }

            return $this->successResponse(__('messages.password_changed'), null);

        } catch (\Exception $e) {
            Log::error('Reset password error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.error') . ': ' . $e->getMessage(),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    /**
     * Get user permissions based on their group
     */
    private function getUserPermissions(User $user)
    {
        try {
            if ($user->admin_group_id == Constants::SUPER_ADMIN_GROUP_ID) {
                return AdminPermission::where('is_active', true)
                    ->pluck('title')
                    ->toArray();
            }

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

    /**
     * Check if user has specific permission
     */
    public function checkPermission(Request $request, $permission)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->failureResponse(
                    __('messages.unauthenticated'),
                    null,
                    Constants::RESPONSE_UNAUTHORIZED
                );
            }

            $hasPermission = $this->hasPermission($user, $permission);

            return $this->successResponse(
                __('messages.permission_check'),
                [
                    'has_permission' => $hasPermission,
                    'permission' => $permission
                ]
            );

        } catch (\Exception $e) {
            Log::error('Check permission error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.error') . ': ' . $e->getMessage(),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    /**
     * Check user permissions in batch
     */
    public function checkPermissionsBatch(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->failureResponse(
                    __('messages.unauthenticated'),
                    null,
                    Constants::RESPONSE_UNAUTHORIZED
                );
            }

            $permissions = $request->input('permissions', []);
            $results = [];

            foreach ($permissions as $permission) {
                $results[$permission] = $this->hasPermission($user, $permission);
            }

            return $this->successResponse(
                __('messages.permissions_check'),
                ['permissions' => $results]
            );

        } catch (\Exception $e) {
            Log::error('Check permissions batch error: ' . $e->getMessage());
            return $this->failureResponse(
                __('messages.error') . ': ' . $e->getMessage(),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    /**
     * Helper method to check permission
     */
    private function hasPermission(User $user, string $permission): bool
    {
        if ($user->admin_group_id == Constants::SUPER_ADMIN_GROUP_ID) {
            return true;
        }

        if ($user->adminGroup && $user->adminGroup->permissions) {
            return $user->adminGroup->permissions
                ->where('is_active', true)
                ->where('title', $permission)
                ->isNotEmpty();
        }

        return false;
    }
}
