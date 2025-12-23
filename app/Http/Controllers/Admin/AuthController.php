<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ResponseTrait;

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->failureResponse($validator->errors()->first(), $validator->errors());
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return $this->failureResponse(__('auth.failed'), null, 401);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user->is_active) {
            return $this->failureResponse(__('auth.inactive'), null, 403);
        }

        // Get user permissions
        $permissions = [];
        if ($user->admin_group_id == \App\Constants\Constants::SUPER_ADMIN_GROUP_ID) {
            $permissions = \App\Models\AdminPermission::where('is_active', 1)
                ->pluck('title')
                ->toArray();
        } else {
            $permissions = $user->adminGroup->permissions()
                ->where('is_active', 1)
                ->pluck('title')
                ->toArray();
        }

        $token = $user->createToken('admin-token')->plainTextToken;

        // Get user menus
        $menuController = new PermissionController();
        $menusResponse = $menuController->getMenus();
        $menus = $menusResponse->getData()->data ?? [];

        return $this->successResponse(__('auth.login_success'), [
            'user' => $user->load('adminGroup'),
            'token' => $token,
            'menu' => $menus,
            'permissions' => $permissions
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(__('auth.logout_success'), null);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        // Get user permissions
        $permissions = [];
        if ($user->admin_group_id == \App\Constants\Constants::SUPER_ADMIN_GROUP_ID) {
            $permissions = \App\Models\AdminPermission::where('is_active', 1)
                ->pluck('title')
                ->toArray();
        } else {
            $permissions = $user->adminGroup->permissions()
                ->where('is_active', 1)
                ->pluck('title')
                ->toArray();
        }

        // Get user menus
        $menuController = new PermissionController();
        $menusResponse = $menuController->getMenus();
        $menus = $menusResponse->getData()->data ?? [];

        return $this->successResponse(__('auth.user_fetched'), [
            'user' => $user->load('adminGroup'),
            'menu' => $menus,
            'permissions' => $permissions
        ]);
    }

    public function refresh(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();

        $token = $user->createToken('admin-token')->plainTextToken;

        return $this->successResponse(__('auth.token_refreshed'), [
            'token' => $token
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return $this->successResponse(__($status), null);
        }

        return $this->failureResponse(__($status), null, 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->successResponse(__($status), null);
        }

        return $this->failureResponse(__($status), null, 400);
    }
}
