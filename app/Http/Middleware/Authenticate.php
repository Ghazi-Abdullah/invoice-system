<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * هذا مشروع API بحت (لا توجد صفحة تسجيل دخول ويب أو route باسم 'login')،
     * فلا داعي لأي محاولة redirect. إرجاع null دائماً يجعل Laravel
     * يرمي AuthenticationException التي تتحول تلقائياً لاستجابة JSON نظيفة (401)
     * لأي طلب غير مصادق عليه، بغض النظر عن الـ Accept header المُرسل.
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}
