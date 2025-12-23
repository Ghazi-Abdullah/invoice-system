<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\PermissionHelper;

class CheckPermission
{
    public function handle($request, Closure $next, $permission)
    {
        if (!PermissionHelper::checkPermission($permission)) {
            return response()->json([
                'status' => false,
                'message' => __('messages.no_permission'),
                'data' => null
            ], 403);
        }

        return $next($request);
    }
}
