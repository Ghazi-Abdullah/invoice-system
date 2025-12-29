<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\PermissionHelper;
use App\Constants\Constants;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!PermissionHelper::checkPermission($permission)) {
            return response()->json([
                'status' => false,
                'message' => __('messages.no_permission'),
                'data' => null
            ], Constants::RESPONSE_FORBIDDEN);
        }

        return $next($request);
    }
}
