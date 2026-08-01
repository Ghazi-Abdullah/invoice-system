<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckIdleTimeout;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckSanctumToken;
use App\Http\Middleware\SanitizeInputMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ── تطبّق على كل طلب (كانت معرّفة في app/Http/Kernel.php
        //    القديم ولم تُنقل عند الترقية لهيكلة Laravel 11) ──────────
        $middleware->append([
            SecurityHeadersMiddleware::class,
            SanitizeInputMiddleware::class,
        ]);

        $middleware->alias([
            // بدون هذا، Laravel يستخدم نسخته الافتراضية من Authenticate
            // (اللي تحاول route('login') غير الموجود) بدل نسختنا المخصصة
            'auth' => Authenticate::class,
            'permission' => CheckPermission::class,
            'check.sanctum' => CheckSanctumToken::class,
            'check.idle' => CheckIdleTimeout::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // هذا مشروع API بحت، فأي طلب غير مصادق عليه يجب أن يرجع
        // JSON نظيف دائماً — بغض النظر عن الـ Accept header المُرسل.
        // بدون هذا، معالج الأخطاء الافتراضي بـ Laravel يحاول route('login')
        // غير الموجود أصلاً ويطلع 500 بدل 401.
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
            ], 401);
        });
    })->create();
