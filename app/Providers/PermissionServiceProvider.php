<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;


use App\Models\Permission;

class PermissionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            // تسجيل الـ Gates للصلاحيات
            $this->registerPermissionGates();
        } catch (\Exception $e) {
            // تجاهل الأخطاء أثناء البوت (لأن الجداول قد لا تكون موجودة بعد)
            if (app()->environment('local')) {
                Log::info('Permission gates registration skipped: ' . $e->getMessage());
            }
        }
    }

    protected function registerPermissionGates(): void
    {
        // التحقق مما إذا كانت جدول الصلاحيات موجود
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissions = Permission::all();

        foreach ($permissions as $permission) {
            Gate::define($permission->name, function ($user) use ($permission) {
                return $user->hasPermission($permission->name);
            });
        }
    }

    public function register(): void
    {
        //
    }
}
