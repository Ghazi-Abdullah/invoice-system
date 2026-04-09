<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecurityHeadersMiddleware
 *
 * يضيف HTTP Security Headers على كل استجابة.
 * يحمي من: XSS, Clickjacking, MIME Sniffing, Info Leakage
 */
class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // ── 1. منع تضمين الصفحة في iframe (حماية من Clickjacking) ─────────
        $response->headers->set('X-Frame-Options', 'DENY');

        // ── 2. منع المتصفح من تخمين نوع المحتوى (MIME Sniffing) ───────────
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // ── 3. تفعيل حماية XSS في المتصفحات القديمة ────────────────────────
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // ── 4. التحكم في Referrer المُرسل مع الطلبات ────────────────────────
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // ── 5. إخفاء نسخة PHP و Server ──────────────────────────────────────
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        // ── 6. Content Security Policy ───────────────────────────────────────
        // يمنع تنفيذ scripts/styles خارجية غير مصرح بها
        // ملاحظة: عدّل هذا حسب CDNs التي تستخدمها في الـ Frontend
        if (config('app.env') === 'production') {
            $response->headers->set(
                'Content-Security-Policy',
                implode('; ', [
                    "default-src 'self'",
                    "script-src 'self' 'unsafe-inline' https://js.stripe.com",
                    "style-src 'self' 'unsafe-inline'",
                    "img-src 'self' data: https:",
                    "font-src 'self' data:",
                    "connect-src 'self' https://api.stripe.com",
                    "frame-src https://js.stripe.com https://hooks.stripe.com",
                    "object-src 'none'",
                    "base-uri 'self'",
                ])
            );
        }

        // ── 7. Permissions Policy (تقييد صلاحيات المتصفح) ──────────────────
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(self)'
        );

        // ── 8. HSTS: إجبار HTTPS (فعّله فقط إذا عندك SSL) ──────────────────
        if (config('app.env') === 'production' && $request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }
}
