<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في التحقق',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // التحقق من حالة المستخدم
            if (!$user->is_active) {
                return response()->json([
                    'status' => false,
                    'message' => 'الحساب غير نشط'
                ], 401);
            }

            // إنشاء التوكن
            $token = $user->createToken('auth_token')->plainTextToken;

            // تحميل العلاقات مع الصلاحيات
            $user->load(['group.permissions']);

            // الحصول على صلاحيات المستخدم
            $permissions = $user->permissions->toArray();
            $is_admin = $user->admin_group_id === 1;

            return response()->json([
                'status' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'permissions' => $permissions,
                    'is_admin' => $is_admin
                ]
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'بيانات الاعتماد غير صحيحة'
        ], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم تسجيل الخروج بنجاح'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        // تحميل العلاقات مع الصلاحيات
        $user->load(['group.permissions']);

        // الحصول على صلاحيات المستخدم
        $permissions = $user->permissions->toArray();
        $is_admin = $user->admin_group_id === 1;

        return response()->json([
            'status' => true,
            'message' => 'بيانات المستخدم',
            'data' => [
                'user' => $user,
                'permissions' => $permissions,
                'is_admin' => $is_admin
            ]
        ]);
    }
}
