<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PermissionSystemSeeder extends Seeder
{
    public function run(): void
    {
        // إيقاف فحص المفاتيح الخارجية مؤقتاً
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // مسح الجداول بالترتيب الصحيح
        DB::table('admin_group_permissions')->truncate();
        DB::table('admin_permissions')->truncate();
        DB::table('admin_sub_menus')->truncate();
        DB::table('admin_menus')->truncate();
        DB::table('users')->truncate();
        DB::table('admin_groups')->truncate();

        // إعادة تفعيل فحص المفاتيح
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. إضافة مجموعات المسؤولين مع الحقول الصحيحة
        $groups = [
            [
                'id' => 1,
                'title_en' => 'Super Admin',
                'title_ar' => 'مدير عام',
                'description' => 'System administrator with full access',
                'is_active' => true,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 2,
                'title_en' => 'Admin',
                'title_ar' => 'مدير',
                'description' => 'Administrator with full access to system features',
                'is_active' => true,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 3,
                'title_en' => 'Accountant',
                'title_ar' => 'محاسب',
                'description' => 'Can manage invoices, payments, and reports',
                'is_active' => true,
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 4,
                'title_en' => 'Sales',
                'title_ar' => 'مبيعات',
                'description' => 'Can manage clients and create invoices',
                'is_active' => true,
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 5,
                'title_en' => 'Client',
                'title_ar' => 'عميل',
                'description' => 'External client who can view their own invoices',
                'is_active' => true,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ];

        DB::table('admin_groups')->insert($groups);

        // 2. إضافة المستخدمين
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@invoice.com',
                'password' => Hash::make('password123'),
                'phone' => '+1234567890',
                'company_name' => 'Invoice System',
                'admin_group_id' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Accountant User',
                'email' => 'accountant@invoice.com',
                'password' => Hash::make('password123'),
                'phone' => '+1234567891',
                'company_name' => 'Accounting Department',
                'admin_group_id' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Sales User',
                'email' => 'sales@invoice.com',
                'password' => Hash::make('password123'),
                'phone' => '+1234567892',
                'company_name' => 'Sales Department',
                'admin_group_id' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ];

        DB::table('users')->insert($users);

        // 3. إضافة القوائم الرئيسية
        $menus = [
            [
                'id' => 1,
                'title_en' => 'Dashboard',
                'title_ar' => 'لوحة التحكم',
                'link' => '/dashboard',
                'icon_class' => 'fa-home',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 2,
                'title_en' => 'Invoices',
                'title_ar' => 'الفواتير',
                'link' => null,
                'icon_class' => 'fa-file-invoice',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 3,
                'title_en' => 'Clients',
                'title_ar' => 'العملاء',
                'link' => null,
                'icon_class' => 'fa-users',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 4,
                'title_en' => 'Reports',
                'title_ar' => 'التقارير',
                'link' => null,
                'icon_class' => 'fa-chart-bar',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 5,
                'title_en' => 'Administration',
                'title_ar' => 'الإدارة',
                'link' => null,
                'icon_class' => 'fa-cog',
                'sort_order' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ];

        DB::table('admin_menus')->insert($menus);

        // 4. إضافة الصلاحيات (مبسطة)
        $permissions = [
            // Dashboard
            ['id' => 1, 'title' => 'view_dashboard', 'description_en' => 'View Dashboard', 'description_ar' => 'عرض لوحة التحكم'],

            // Invoices
            ['id' => 2, 'title' => 'view_invoices', 'description_en' => 'View Invoices', 'description_ar' => 'عرض الفواتير'],
            ['id' => 3, 'title' => 'create_invoice', 'description_en' => 'Create Invoice', 'description_ar' => 'إنشاء فاتورة'],
            ['id' => 4, 'title' => 'edit_invoice', 'description_en' => 'Edit Invoice', 'description_ar' => 'تعديل فاتورة'],
            ['id' => 5, 'title' => 'delete_invoice', 'description_en' => 'Delete Invoice', 'description_ar' => 'حذف فاتورة'],

            // Clients
            ['id' => 6, 'title' => 'view_clients', 'description_en' => 'View Clients', 'description_ar' => 'عرض العملاء'],
            ['id' => 7, 'title' => 'create_client', 'description_en' => 'Create Client', 'description_ar' => 'إنشاء عميل'],
            ['id' => 8, 'title' => 'edit_client', 'description_en' => 'Edit Client', 'description_ar' => 'تعديل عميل'],
            ['id' => 9, 'title' => 'delete_client', 'description_en' => 'Delete Client', 'description_ar' => 'حذف عميل'],

            // Users
            ['id' => 10, 'title' => 'view_users', 'description_en' => 'View Users', 'description_ar' => 'عرض المستخدمين'],
            ['id' => 11, 'title' => 'create_user', 'description_en' => 'Create User', 'description_ar' => 'إنشاء مستخدم'],
            ['id' => 12, 'title' => 'edit_user', 'description_en' => 'Edit User', 'description_ar' => 'تعديل مستخدم'],
            ['id' => 13, 'title' => 'delete_user', 'description_en' => 'Delete User', 'description_ar' => 'حذف مستخدم'],

            // Admin Groups
            ['id' => 14, 'title' => 'view_admin_groups', 'description_en' => 'View Admin Groups', 'description_ar' => 'عرض مجموعات الإدارة'],
            ['id' => 15, 'title' => 'manage_admin_groups', 'description_en' => 'Manage Admin Groups', 'description_ar' => 'إدارة مجموعات الإدارة'],

            // Reports
            ['id' => 16, 'title' => 'view_reports', 'description_en' => 'View Reports', 'description_ar' => 'عرض التقارير'],
            ['id' => 17, 'title' => 'export_reports', 'description_en' => 'Export Reports', 'description_ar' => 'تصدير التقارير'],

            // Permissions
            ['id' => 18, 'title' => 'manage_permissions', 'description_en' => 'Manage Permissions', 'description_ar' => 'إدارة الصلاحيات'],
        ];

        foreach ($permissions as $permission) {
            DB::table('admin_permissions')->insert(array_merge($permission, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }

        // 5. تعيين جميع الصلاحيات لـ Super Admin (ID: 1)
        foreach ($permissions as $permission) {
            DB::table('admin_group_permissions')->insert([
                'admin_group_id' => 1,
                'admin_permission_id' => $permission['id'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // 6. تعيين صلاحيات المحاسب (ID: 3)
        $accountantPermissions = [1, 2, 3, 4, 5, 6, 7, 8, 9, 16, 17];
        foreach ($accountantPermissions as $permissionId) {
            DB::table('admin_group_permissions')->insert([
                'admin_group_id' => 3,
                'admin_permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // 7. تعيين صلاحيات المبيعات (ID: 4)
        $salesPermissions = [1, 2, 3, 6, 7, 8, 9];
        foreach ($salesPermissions as $permissionId) {
            DB::table('admin_group_permissions')->insert([
                'admin_group_id' => 4,
                'admin_permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
