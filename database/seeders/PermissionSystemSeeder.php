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

        // مسح الجداول
        DB::table('admin_group_permissions')->truncate();
        DB::table('admin_permissions')->truncate();
        DB::table('admin_sub_menus')->truncate();
        DB::table('admin_menus')->truncate();
        DB::table('users')->truncate();
        DB::table('admin_groups')->truncate();

        // إعادة تفعيل فحص المفاتيح
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. إضافة مجموعات المسؤولين
        DB::table('admin_groups')->insert([
            ['id' => 1, 'title_en' => 'Administrator', 'title_ar' => 'مدير', 'is_active' => true],
            ['id' => 2, 'title_en' => 'Accountant', 'title_ar' => 'محاسب', 'is_active' => true],
            ['id' => 3, 'title_en' => 'Sales', 'title_ar' => 'مبيعات', 'is_active' => true],
            ['id' => 4, 'title_en' => 'View Only', 'title_ar' => 'عرض فقط', 'is_active' => true],
        ]);

        // 2. إضافة القوائم الرئيسية
        DB::table('admin_menus')->insert([
            ['id' => 1, 'title_en' => 'Dashboard', 'title_ar' => 'لوحة القيادة', 'link' => '/dashboard', 'icon_class' => 'home', 'sort_order' => 1],
            ['id' => 2, 'title_en' => 'Clients', 'title_ar' => 'العملاء', 'link' => null, 'icon_class' => 'users', 'sort_order' => 2],
            ['id' => 3, 'title_en' => 'Invoices', 'title_ar' => 'الفواتير', 'link' => null, 'icon_class' => 'file-text', 'sort_order' => 3],
            ['id' => 4, 'title_en' => 'Reports', 'title_ar' => 'التقارير', 'link' => null, 'icon_class' => 'bar-chart', 'sort_order' => 4],
            ['id' => 5, 'title_en' => 'Administration', 'title_ar' => 'الإدارة', 'link' => null, 'icon_class' => 'settings', 'sort_order' => 5],
        ]);

        // 3. إضافة القوائم الفرعية
        DB::table('admin_sub_menus')->insert([
            ['id' => 1, 'admin_menu_id' => 2, 'title_en' => 'All Clients', 'title_ar' => 'جميع العملاء', 'link' => '/clients', 'sort_order' => 1],
            ['id' => 2, 'admin_menu_id' => 2, 'title_en' => 'Add Client', 'title_ar' => 'إضافة عميل', 'link' => '/clients/create', 'sort_order' => 2],
            ['id' => 3, 'admin_menu_id' => 3, 'title_en' => 'All Invoices', 'title_ar' => 'جميع الفواتير', 'link' => '/invoices', 'sort_order' => 1],
            ['id' => 4, 'admin_menu_id' => 3, 'title_en' => 'Create Invoice', 'title_ar' => 'إنشاء فاتورة', 'link' => '/invoices/create', 'sort_order' => 2],
            ['id' => 5, 'admin_menu_id' => 4, 'title_en' => 'Sales Report', 'title_ar' => 'تقرير المبيعات', 'link' => '/reports/sales', 'sort_order' => 1],
            ['id' => 6, 'admin_menu_id' => 5, 'title_en' => 'User Groups', 'title_ar' => 'مجموعات المستخدمين', 'link' => '/admin/groups', 'sort_order' => 1],
            ['id' => 7, 'admin_menu_id' => 5, 'title_en' => 'Users', 'title_ar' => 'المستخدمون', 'link' => '/admin/users', 'sort_order' => 2],
            ['id' => 8, 'admin_menu_id' => 5, 'title_en' => 'Permissions', 'title_ar' => 'الصلاحيات', 'link' => '/admin/permissions', 'sort_order' => 3],
        ]);

        // 4. إضافة الصلاحيات
        $permissions = [
            ['id' => 1, 'admin_menu_id' => 1, 'admin_sub_menu_id' => null, 'parent_id' => 0, 'title' => 'dashboard', 'description_en' => 'View Dashboard', 'description_ar' => 'عرض لوحة التحكم', 'is_parent' => true],
            ['id' => 2, 'admin_menu_id' => 2, 'admin_sub_menu_id' => 1, 'parent_id' => 0, 'title' => 'clients', 'description_en' => 'View Clients', 'description_ar' => 'عرض العملاء', 'is_parent' => true],
            ['id' => 3, 'admin_menu_id' => 2, 'admin_sub_menu_id' => 1, 'parent_id' => 2, 'title' => 'view_clients', 'description_en' => 'View All Clients', 'description_ar' => 'عرض جميع العملاء', 'is_parent' => false],
            ['id' => 4, 'admin_menu_id' => 2, 'admin_sub_menu_id' => 2, 'parent_id' => 2, 'title' => 'create_client', 'description_en' => 'Create Client', 'description_ar' => 'إنشاء عميل', 'is_parent' => false],
            ['id' => 5, 'admin_menu_id' => 2, 'admin_sub_menu_id' => 1, 'parent_id' => 2, 'title' => 'edit_client', 'description_en' => 'Edit Client', 'description_ar' => 'تعديل عميل', 'is_parent' => false],
            ['id' => 6, 'admin_menu_id' => 2, 'admin_sub_menu_id' => 1, 'parent_id' => 2, 'title' => 'delete_client', 'description_en' => 'Delete Client', 'description_ar' => 'حذف عميل', 'is_parent' => false],
            ['id' => 7, 'admin_menu_id' => 3, 'admin_sub_menu_id' => 3, 'parent_id' => 0, 'title' => 'invoices', 'description_en' => 'View Invoices', 'description_ar' => 'عرض الفواتير', 'is_parent' => true],
            ['id' => 8, 'admin_menu_id' => 3, 'admin_sub_menu_id' => 3, 'parent_id' => 7, 'title' => 'view_invoices', 'description_en' => 'View All Invoices', 'description_ar' => 'عرض جميع الفواتير', 'is_parent' => false],
            ['id' => 9, 'admin_menu_id' => 3, 'admin_sub_menu_id' => 4, 'parent_id' => 7, 'title' => 'create_invoice', 'description_en' => 'Create Invoice', 'description_ar' => 'إنشاء فاتورة', 'is_parent' => false],
            ['id' => 10, 'admin_menu_id' => 3, 'admin_sub_menu_id' => 3, 'parent_id' => 7, 'title' => 'edit_invoice', 'description_en' => 'Edit Invoice', 'description_ar' => 'تعديل فاتورة', 'is_parent' => false],
            ['id' => 11, 'admin_menu_id' => 3, 'admin_sub_menu_id' => 3, 'parent_id' => 7, 'title' => 'delete_invoice', 'description_en' => 'Delete Invoice', 'description_ar' => 'حذف فاتورة', 'is_parent' => false],
            ['id' => 12, 'admin_menu_id' => 3, 'admin_sub_menu_id' => 3, 'parent_id' => 7, 'title' => 'view_invoice_details', 'description_en' => 'View Invoice Details', 'description_ar' => 'عرض تفاصيل الفاتورة', 'is_parent' => false],
            ['id' => 13, 'admin_menu_id' => 3, 'admin_sub_menu_id' => 3, 'parent_id' => 7, 'title' => 'print_invoice', 'description_en' => 'Print Invoice', 'description_ar' => 'طباعة الفاتورة', 'is_parent' => false],
            ['id' => 14, 'admin_menu_id' => 4, 'admin_sub_menu_id' => 5, 'parent_id' => 0, 'title' => 'reports', 'description_en' => 'View Reports', 'description_ar' => 'عرض التقارير', 'is_parent' => true],
            ['id' => 15, 'admin_menu_id' => 4, 'admin_sub_menu_id' => 5, 'parent_id' => 14, 'title' => 'view_sales_report', 'description_en' => 'View Sales Report', 'description_ar' => 'عرض تقرير المبيعات', 'is_parent' => false],
            ['id' => 16, 'admin_menu_id' => 4, 'admin_sub_menu_id' => 5, 'parent_id' => 14, 'title' => 'export_reports', 'description_en' => 'Export Reports', 'description_ar' => 'تصدير التقارير', 'is_parent' => false],
            ['id' => 17, 'admin_menu_id' => 5, 'admin_sub_menu_id' => 6, 'parent_id' => 0, 'title' => 'administration', 'description_en' => 'Administration Access', 'description_ar' => 'الوصول للإدارة', 'is_parent' => true],
            ['id' => 18, 'admin_menu_id' => 5, 'admin_sub_menu_id' => 6, 'parent_id' => 17, 'title' => 'manage_user_groups', 'description_en' => 'Manage User Groups', 'description_ar' => 'إدارة مجموعات المستخدمين', 'is_parent' => false],
            ['id' => 19, 'admin_menu_id' => 5, 'admin_sub_menu_id' => 7, 'parent_id' => 17, 'title' => 'manage_users', 'description_en' => 'Manage Users', 'description_ar' => 'إدارة المستخدمين', 'is_parent' => false],
            ['id' => 20, 'admin_menu_id' => 5, 'admin_sub_menu_id' => 8, 'parent_id' => 17, 'title' => 'manage_permissions', 'description_en' => 'Manage Permissions', 'description_ar' => 'إدارة الصلاحيات', 'is_parent' => false],
        ];

        foreach ($permissions as $permission) {
            DB::table('admin_permissions')->insert($permission);
        }

        // 5. إضافة المستخدمين
        $users = [
            ['name' => 'Admin', 'email' => 'admin@invoice.com', 'password' => Hash::make('password'), 'admin_group_id' => 1, 'is_active' => true],
            ['name' => 'Accountant', 'email' => 'accountant@invoice.com', 'password' => Hash::make('password'), 'admin_group_id' => 2, 'is_active' => true],
            ['name' => 'Sales', 'email' => 'sales@invoice.com', 'password' => Hash::make('password'), 'admin_group_id' => 3, 'is_active' => true],
        ];

        foreach ($users as $user) {
            DB::table('users')->insert($user);
        }

        // 6. تعيين الصلاحيات للمجموعات
        // Administrator has all permissions
        for ($i = 1; $i <= 20; $i++) {
            DB::table('admin_group_permissions')->insert([
                'admin_group_id' => 1,
                'admin_permission_id' => $i
            ]);
        }

        // Accountant permissions
        $accountantPermissions = [1, 2, 3, 7, 8, 12, 13, 14, 15, 16];
        foreach ($accountantPermissions as $permissionId) {
            DB::table('admin_group_permissions')->insert([
                'admin_group_id' => 2,
                'admin_permission_id' => $permissionId
            ]);
        }

        // Sales permissions
        $salesPermissions = [1, 2, 3, 4, 5, 7, 8, 9, 10, 12, 13];
        foreach ($salesPermissions as $permissionId) {
            DB::table('admin_group_permissions')->insert([
                'admin_group_id' => 3,
                'admin_permission_id' => $permissionId
            ]);
        }
    }
}
