<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitizeInputMiddleware
{
    /**
     * ✅ تنظيف المدخلات من XSS قبل أي معالجة
     * ✅ لا تُطبق على Stripe Webhook (raw body)
     */
    public function handle(Request $request, Closure $next)
    {
        // ✅ استثناء Webhook Stripe (يحتاج raw body)
        if ($request->is('*/stripe/*') || $request->is('*/payments/webhook')) {
            return $next($request);
        }

        $input = $request->all();
        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                $value = strip_tags($value);
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        });
        $request->merge($input);

        return $next($request);
    }
}
