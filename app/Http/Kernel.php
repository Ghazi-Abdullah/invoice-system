<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class Kernel extends HttpKernel
{
    protected $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\SecurityHeadersMiddleware::class,
        \App\Http\Middleware\SanitizeInputMiddleware::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    protected $middlewareAliases = [
        'auth'             => \App\Http\Middleware\Authenticate::class,
        'auth.basic'       => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session'     => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers'    => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can'              => \Illuminate\Auth\Middleware\Authorize::class,
        'guest'            => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed'           => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle'         => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified'         => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'check.sanctum'    => \App\Http\Middleware\CheckSanctumToken::class,
        'idle.timeout'     => \App\Http\Middleware\CheckIdleTimeout::class,
        'permission'       => \App\Http\Middleware\CheckPermission::class,
        'admin'            => \App\Http\Middleware\AdminMiddleware::class,
    ];

    public static function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'لقد تجاوزت الحد المسموح به من الطلبات. حاول بعد دقيقة.',
                        'retry_after' => 60,
                    ], 429);
                });
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'محاولات كثيرة. الرجاء الانتظار دقيقة قبل المحاولة مجدداً.',
                        'retry_after' => 60,
                    ], 429);
                });
        });

        RateLimiter::for('exports', function (Request $request) {
            return Limit::perMinutes(5, 10)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'حد التصدير: 10 ملفات كل 5 دقائق. حاول لاحقاً.',
                        'retry_after' => 300,
                    ], 429);
                });
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinutes(15, 3)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'لا يمكن طلب إعادة تعيين كلمة المرور أكثر من 3 مرات كل 15 دقيقة.',
                        'retry_after' => 900,
                    ], 429);
                });
        });

        RateLimiter::for('otp', function (Request $request) {
            return Limit::perMinutes(5, 3)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'لا يمكن طلب رمز التحقق أكثر من 3 مرات كل 5 دقائق.',
                        'retry_after' => 300,
                    ], 429);
                });
        });
    }

    public function boot(): void
    {
        parent::boot();
        self::configureRateLimiting();
    }
}
