<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SanitizeInputMiddleware
 *
 * يُنظّف جميع المدخلات النصية من HTML/JS ضار.
 * يحمي من XSS عند حفظ البيانات في قاعدة البيانات.
 *
 * الحقول المستثناة: كلمات المرور + Stripe Webhook body
 */
class SanitizeInputMiddleware
{
    /**
     * حقول لا تُنظَّف أبداً
     */
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
    ];

    /**
     * Routes لا تُطبّق عليها التنظيف (مثل Stripe Webhook)
     */
    protected array $exceptRoutes = [
        'api/admin/payments/webhook',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // تخطي Stripe Webhook (يحتاج body خاماً للتحقق من التوقيع)
        foreach ($this->exceptRoutes as $route) {
            if ($request->is($route)) {
                return $next($request);
            }
        }

        // تنظيف جميع المدخلات
        $input = $request->all();
        $cleaned = $this->cleanArray($input);
        $request->replace($cleaned);

        return $next($request);
    }

    private function cleanArray(array $data): array
    {
        $cleaned = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $this->except, true)) {
                // حقول محظورة — لا تُلمس
                $cleaned[$key] = $value;
            } elseif (is_array($value)) {
                // مصفوفة (مثل items[]) — نظّف بشكل متكرر
                $cleaned[$key] = $this->cleanArray($value);
            } elseif (is_string($value)) {
                // نص — أزل HTML/JS الخطير
                $cleaned[$key] = $this->cleanString($value);
            } else {
                // أرقام، booleans، null — تُترك كما هي
                $cleaned[$key] = $value;
            }
        }
        return $cleaned;
    }

    private function cleanString(string $value): string
    {
        // 1. أزل HTML tags الخطيرة مع الحفاظ على النص
        $value = strip_tags($value);

        // 2. حوّل الحروف الخاصة إلى HTML entities
        //    ENT_QUOTES: يشمل ' و "
        //    false: لا تحذف newlines
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);

        // 3. أزل null bytes (قد تستخدم لتجاوز الفلاتر)
        $value = str_replace("\0", '', $value);

        return $value;
    }
}
