<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSanctumToken
{
    /**
     * ✅ التحقق من صلاحية توكن Sanctum
     * ✅ التحقق من تفعيل الحساب
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => __('auth.unauthenticated'),
                'data' => null
            ], 401);
        }

        // ✅ التحقق من تفعيل الحساب حتى لو كان التوكن صالحاً
        if (!$user->is_active) {
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'status' => false,
                'message' => __('auth.inactive_account'),
                'data' => null
            ], 403);
        }

        return $next($request);
    }
}
