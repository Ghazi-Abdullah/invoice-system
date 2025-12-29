<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;

class CheckSanctumToken
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->bearerToken()) {
            return response()->json([
                'status' => false,
                'message' => 'Token not provided',
                'data' => null
            ], 401);
        }

        // تحقق من صحة التوكن
        $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());

        if (!$token) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid token',
                'data' => null
            ], 401);
        }

        // تحقق من انتهاء صلاحية التوكن
        if ($token->expires_at && $token->expires_at->isPast()) {
            $token->delete();
            return response()->json([
                'status' => false,
                'message' => 'Token expired',
                'data' => null
            ], 401);
        }

        // المصادقة على المستخدم
        $user = $token->tokenable;

        if (!$user || !$user->is_active) {
            return response()->json([
                'status' => false,
                'message' => 'User not found or inactive',
                'data' => null
            ], 401);
        }

        // تسجيل دخول المستخدم
        Auth::setUser($user);

        return $next($request);
    }
}
