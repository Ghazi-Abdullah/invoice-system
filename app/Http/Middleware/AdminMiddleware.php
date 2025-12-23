<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Constants\Constants;

class AdminMiddleware
{
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

        // Check if user is not a client (clients should use user API)
        if ($user->isClient()) {
            return response()->json([
                'status' => false,
                'message' => __('auth.not_admin'),
                'data' => null
            ], 403);
        }

        return $next($request);
    }
}
