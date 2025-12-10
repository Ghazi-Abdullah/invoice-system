<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'غير مصرح'], 401);
        }

        // التحقق من أن المستخدم لديه دور admin
        if (!$user->hasRole('admin')) {
            return response()->json(['message' => 'لا تملك صلاحية الوصول لهذا القسم'], 403);
        }

        return $next($request);
    }
}
