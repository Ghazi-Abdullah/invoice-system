<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::table('admin_permissions')->truncate();
        DB::table('admin_permissions')->insert([
            // Dashboard Permission
            [
                'id' => 1,
                'title' => 'dashboard',
                'description_en' => 'View Dashboard',
                'description_ar' => 'عرض لوحة التحكم',
                'parent_id' => 0,
                'admin_menu_id' => 1,
                'admin_sub_menu_id' => null,
                'is_parent' => true
            ],

            // Clients Permissions
            [
                'id' => 2,
                'title' => 'clients',
                'description_en' => 'View Clients',
                'description_ar' => 'عرض العملاء',
                'parent_id' => 0,
                'admin_menu_id' => 2,
                'admin_sub_menu_id' => 1,
                'is_parent' => true
            ],
            [
                'id' => 3,
                'title' => 'view_clients',
                'description_en' => 'View All Clients',
                'description_ar' => 'عرض جميع العملاء',
                'parent_id' => 2,
                'admin_menu_id' => 2,
                'admin_sub_menu_id' => 1,
                'is_parent' => false
            ],
            [
                'id' => 4,
                'title' => 'create_client',
                'description_en' => 'Create Client',
                'description_ar' => 'إنشاء عميل',
                'parent_id' => 2,
                'admin_menu_id' => 2,
                'admin_sub_menu_id' => 2,
                'is_parent' => false
            ],
            [
                'id' => 5,
                'title' => 'edit_client',
                'description_en' => 'Edit Client',
                'description_ar' => 'تعديل عميل',
                'parent_id' => 2,
                'admin_menu_id' => 2,
                'admin_sub_menu_id' => 1,
                'is_parent' => false
            ],
            [
                'id' => 6,
                'title' => 'delete_client',
                'description_en' => 'Delete Client',
                'description_ar' => 'حذف عميل',
                'parent_id' => 2,
                'admin_menu_id' => 2,
                'admin_sub_menu_id' => 1,
                'is_parent' => false
            ],

            // Invoices Permissions
            [
                'id' => 7,
                'title' => 'invoices',
                'description_en' => 'View Invoices',
                'description_ar' => 'عرض الفواتير',
                'parent_id' => 0,
                'admin_menu_id' => 3,
                'admin_sub_menu_id' => 1,
                'is_parent' => true
            ],
            [
                'id' => 8,
                'title' => 'view_invoices',
                'description_en' => 'View All Invoices',
                'description_ar' => 'عرض جميع الفواتير',
                'parent_id' => 7,
                'admin_menu_id' => 3,
                'admin_sub_menu_id' => 1,
                'is_parent' => false
            ],
            [
                'id' => 9,
                'title' => 'create_invoice',
                'description_en' => 'Create Invoice',
                'description_ar' => 'إنشاء فاتورة',
                'parent_id' => 7,
                'admin_menu_id' => 3,
                'admin_sub_menu_id' => 2,
                'is_parent' => false
            ],
            [
                'id' => 10,
                'title' => 'edit_invoice',
                'description_en' => 'Edit Invoice',
                'description_ar' => 'تعديل فاتورة',
                'parent_id' => 7,
                'admin_menu_id' => 3,
                'admin_sub_menu_id' => 1,
                'is_parent' => false
            ],
            [
                'id' => 11,
                'title' => 'delete_invoice',
                'description_en' => 'Delete Invoice',
                'description_ar' => 'حذف فاتورة',
                'parent_id' => 7,
                'admin_menu_id' => 3,
                'admin_sub_menu_id' => 1,
                'is_parent' => false
            ],
            [
                'id' => 12,
                'title' => 'view_invoice_details',
                'description_en' => 'View Invoice Details',
                'description_ar' => 'عرض تفاصيل الفاتورة',
                'parent_id' => 7,
                'admin_menu_id' => 3,
                'admin_sub_menu_id' => 1,
                'is_parent' => false
            ],
            [
                'id' => 13,
                'title' => 'print_invoice',
                'description_en' => 'Print Invoice',
                'description_ar' => 'طباعة الفاتورة',
                'parent_id' => 7,
                'admin_menu_id' => 3,
                'admin_sub_menu_id' => 1,
                'is_parent' => false
            ],

            // Reports Permissions
            [
                'id' => 14,
                'title' => 'reports',
                'description_en' => 'View Reports',
                'description_ar' => 'عرض التقارير',
                'parent_id' => 0,
                'admin_menu_id' => 4,
                'admin_sub_menu_id' => 1,
                'is_parent' => true
            ],
            [
                'id' => 15,
                'title' => 'view_sales_report',
                'description_en' => 'View Sales Report',
                'description_ar' => 'عرض تقرير المبيعات',
                'parent_id' => 14,
                'admin_menu_id' => 4,
                'admin_sub_menu_id' => 1,
                'is_parent' => false
            ],
            [
                'id' => 16,
                'title' => 'export_reports',
                'description_en' => 'Export Reports',
                'description_ar' => 'تصدير التقارير',
                'parent_id' => 14,
                'admin_menu_id' => 4,
                'admin_sub_menu_id' => 1,
                'is_parent' => false
            ],

            // Administration Permissions
            [
                'id' => 17,
                'title' => 'administration',
                'description_en' => 'Administration Access',
                'description_ar' => 'الوصول للإدارة',
                'parent_id' => 0,
                'admin_menu_id' => 5,
                'admin_sub_menu_id' => 1,
                'is_parent' => true
            ],
            [
                'id' => 18,
                'title' => 'manage_user_groups',
                'description_en' => 'Manage User Groups',
                'description_ar' => 'إدارة مجموعات المستخدمين',
                'parent_id' => 17,
                'admin_menu_id' => 5,
                'admin_sub_menu_id' => 1,
                'is_parent' => false
            ],
            [
                'id' => 19,
                'title' => 'manage_users',
                'description_en' => 'Manage Users',
                'description_ar' => 'إدارة المستخدمين',
                'parent_id' => 17,
                'admin_menu_id' => 5,
                'admin_sub_menu_id' => 2,
                'is_parent' => false
            ],
            [
                'id' => 20,
                'title' => 'manage_permissions',
                'description_en' => 'Manage Permissions',
                'description_ar' => 'إدارة الصلاحيات',
                'parent_id' => 17,
                'admin_menu_id' => 5,
                'admin_sub_menu_id' => 3,
                'is_parent' => false
            ],
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
