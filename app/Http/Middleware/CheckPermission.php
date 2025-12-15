<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح بالوصول'
            ], 401);
        }

        // تحميل العلاقات إذا لزم الأمر
        if (!method_exists($user, 'hasPermission')) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في نظام الصلاحيات'
            ], 500);
        }

        // التحقق من الصلاحية
        if (!$user->hasPermission($permission)) {
            return response()->json([
                'status' => false,
                'message' => 'ليس لديك صلاحية ' . $permission
            ], 403);
        }

        return $next($request);
    }
}
