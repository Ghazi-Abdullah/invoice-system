<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Used by RedirectIfAuthenticated to send already-authenticated users somewhere.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * ملاحظة: تسجيل الراوتات وRate Limiters يتم بالكامل عبر
     * bootstrap/app.php (withRouting) وAppServiceProvider::boot()
     * (Kernel::configureRateLimiting). لا داعي لتكرارهما هنا —
     * كان هذا الملف يسجّل routes/api.php و routes/web.php مرة ثانية
     * ويكتب فوق الـ rate limiter الصحيح بنسخة أضعف بدون رسائل عربية.
     */
    public function boot()
    {
        //
    }
}
