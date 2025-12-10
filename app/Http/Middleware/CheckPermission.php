<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!$user->hasPermission($permission)) {
            return response()->json(['message' => 'Unauthorized - لا تملك الصلاحية للقيام بهذا الإجراء'], 403);
        }

        return $next($request);
    }
}
