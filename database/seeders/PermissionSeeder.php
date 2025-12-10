<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'view_dashboard', 'description' => 'عرض لوحة التحكم'],
            ['name' => 'view_invoices', 'description' => 'عرض الفواتير'],
            ['name' => 'create_invoices', 'description' => 'إنشاء فواتير'],
            ['name' => 'edit_invoices', 'description' => 'تعديل الفواتير'],
            ['name' => 'delete_invoices', 'description' => 'حذف الفواتير'],
            ['name' => 'export_invoices', 'description' => 'تصدير الفواتير'],
            ['name' => 'view_clients', 'description' => 'عرض العملاء'],
            ['name' => 'create_clients', 'description' => 'إضافة عملاء'],
            ['name' => 'edit_clients', 'description' => 'تعديل العملاء'],
            ['name' => 'delete_clients', 'description' => 'حذف العملاء'],
            ['name' => 'view_reports', 'description' => 'عرض التقارير'],
            ['name' => 'export_reports', 'description' => 'تصدير التقارير'],
            ['name' => 'view_users', 'description' => 'عرض المستخدمين'],
            ['name' => 'create_users', 'description' => 'إضافة مستخدمين'],
            ['name' => 'edit_users', 'description' => 'تعديل المستخدمين'],
            ['name' => 'delete_users', 'description' => 'حذف المستخدمين'],
            ['name' => 'manage_permissions', 'description' => 'إدارة الصلاحيات'],
            ['name' => 'manage_settings', 'description' => 'إدارة الإعدادات']
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                ['description' => $permission['description']]
            );
        }

        $adminRole = Role::updateOrCreate(
            ['name' => 'admin'],
            ['description' => 'مدير النظام']
        );

        $userRole = Role::updateOrCreate(
            ['name' => 'user'],
            ['description' => 'مستخدم عادي']
        );

        $accountantRole = Role::updateOrCreate(
            ['name' => 'accountant'],
            ['description' => 'محاسب']
        );

        $adminRole->permissions()->sync(Permission::all()->pluck('id'));

        $userRole->permissions()->sync(Permission::whereIn('name', [
            'view_dashboard', 'view_invoices', 'create_invoices', 'view_clients'
        ])->pluck('id'));

        $accountantRole->permissions()->sync(Permission::whereIn('name', [
            'view_dashboard', 'view_invoices', 'create_invoices', 'edit_invoices',
            'view_clients', 'view_reports', 'export_reports'
        ])->pluck('id'));

        $firstUser = \App\Models\User::first();
        if ($firstUser) {
            $firstUser->roles()->sync([$adminRole->id]);
        }
    }
}
