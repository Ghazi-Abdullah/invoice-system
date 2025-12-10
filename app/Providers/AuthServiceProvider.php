<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot()
    {
        $this->registerPolicies();

        // تسجيل جميع Gates للصلاحيات
        $this->registerPermissionGates();
    }

    protected function registerPermissionGates(): void
    {
        try {
            // تسجيل Gates للصلاحيات الموجودة في قاعدة البيانات
            $permissions = Permission::all();

            foreach ($permissions as $permission) {
                Gate::define($permission->name, function (User $user) use ($permission) {
                    return $this->checkUserPermission($user->id, $permission->name);
                });
            }

        } catch (\Exception $e) {
            Log::error('Error registering permission gates: ' . $e->getMessage());
        }
    }

    protected function checkUserPermission(int $userId, string $permissionName): bool
    {
        return DB::table('users')
            ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->join('role_permissions', 'roles.id', '=', 'role_permissions.role_id')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('users.id', $userId)
            ->where('permissions.name', $permissionName)
            ->exists();
    }
}
