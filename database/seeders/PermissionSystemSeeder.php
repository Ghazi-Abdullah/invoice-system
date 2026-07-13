<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PermissionSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncate tables in correct order
        DB::table('admin_group_permissions')->truncate();
        DB::table('admin_permissions')->truncate();
        DB::table('admin_sub_menus')->truncate();
        DB::table('admin_menus')->truncate();
        DB::table('users')->truncate();
        DB::table('admin_groups')->truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Insert admin groups
        $groups = [
            [
                'id'          => 1,
                'title_en'    => 'Super Admin',
                'title_ar'    => 'مدير عام',
                'description' => 'System administrator with full access',
                'is_active'   => true,
                'is_system'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 2,
                'title_en'    => 'Admin',
                'title_ar'    => 'مدير',
                'description' => 'Administrator with full access to system features',
                'is_active'   => true,
                'is_system'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 3,
                'title_en'    => 'Accountant',
                'title_ar'    => 'محاسب',
                'description' => 'Can manage invoices, payments, and reports',
                'is_active'   => true,
                'is_system'   => false,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 4,
                'title_en'    => 'Sales',
                'title_ar'    => 'مبيعات',
                'description' => 'Can manage clients and create invoices',
                'is_active'   => true,
                'is_system'   => false,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 5,
                'title_en'    => 'Client',
                'title_ar'    => 'عميل',
                'description' => 'External client who can view their own invoices',
                'is_active'   => true,
                'is_system'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        DB::table('admin_groups')->insert($groups);

        // 2. Insert users
        $users = [
            [
                'name'           => 'Super Admin',
                'email'          => 'admin@invoice.com',
                'password'       => Hash::make('password123'),
                'phone'          => '+1234567890',
                'company_name'   => 'Invoice System',
                'admin_group_id' => 1,
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'name'           => 'Accountant User',
                'email'          => 'accountant@invoice.com',
                'password'       => Hash::make('password123'),
                'phone'          => '+1234567891',
                'company_name'   => 'Accounting Department',
                'admin_group_id' => 3,
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'name'           => 'Sales User',
                'email'          => 'sales@invoice.com',
                'password'       => Hash::make('password123'),
                'phone'          => '+1234567892',
                'company_name'   => 'Sales Department',
                'admin_group_id' => 4,
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ];

        DB::table('users')->insert($users);

        // 3. Insert main menus
        $menus = [
            [
                'id'          => 1,
                'title_en'    => 'Dashboard',
                'title_ar'    => 'لوحة التحكم',
                'link'        => '/dashboard',
                'icon_class'  => 'fa-home',
                'sort_order'  => 1,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 2,
                'title_en'    => 'Invoices',
                'title_ar'    => 'الفواتير',
                'link'        => null,
                'icon_class'  => 'fa-file-invoice',
                'sort_order'  => 2,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 3,
                'title_en'    => 'Clients',
                'title_ar'    => 'العملاء',
                'link'        => null,
                'icon_class'  => 'fa-users',
                'sort_order'  => 3,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 4,
                'title_en'    => 'Reports',
                'title_ar'    => 'التقارير',
                'link'        => null,
                'icon_class'  => 'fa-chart-bar',
                'sort_order'  => 4,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id'          => 5,
                'title_en'    => 'Administration',
                'title_ar'    => 'الإدارة',
                'link'        => null,
                'icon_class'  => 'fa-cog',
                'sort_order'  => 5,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        DB::table('admin_menus')->insert($menus);

        // 4. Insert permissions with the new columns (admin_menu_id, admin_sub_menu_id, parent_id, is_parent)
        // Note: admin_sub_menu_id is set to null for simplicity; you can adjust if you have sub-menus.
        $permissions = [
            // Dashboard
            [
                'id'                => 1,
                'title'             => 'view_dashboard',
                'description_en'    => 'View Dashboard',
                'description_ar'    => 'عرض لوحة التحكم',
                'admin_menu_id'     => 1,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
            // Invoices
            [
                'id'                => 2,
                'title'             => 'view_invoices',
                'description_en'    => 'View Invoices',
                'description_ar'    => 'عرض الفواتير',
                'admin_menu_id'     => 2,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
            [
                'id'                => 3,
                'title'             => 'create_invoice',
                'description_en'    => 'Create Invoice',
                'description_ar'    => 'إنشاء فاتورة',
                'admin_menu_id'     => 2,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
            [
                'id'                => 4,
                'title'             => 'edit_invoice',
                'description_en'    => 'Edit Invoice',
                'description_ar'    => 'تعديل فاتورة',
                'admin_menu_id'     => 2,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
            [
                'id'                => 5,
                'title'             => 'delete_invoice',
                'description_en'    => 'Delete Invoice',
                'description_ar'    => 'حذف فاتورة',
                'admin_menu_id'     => 2,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
            // Clients
            [
                'id'                => 6,
                'title'             => 'view_clients',
                'description_en'    => 'View Clients',
                'description_ar'    => 'عرض العملاء',
                'admin_menu_id'     => 3,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
            [
                'id'                => 7,
                'title'             => 'create_client',
                'description_en'    => 'Create Client',
                'description_ar'    => 'إنشاء عميل',
                'admin_menu_id'     => 3,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
            [
                'id'                => 8,
                'title'             => 'edit_client',
                'description_en'    => 'Edit Client',
                'description_ar'    => 'تعديل عميل',
                'admin_menu_id'     => 3,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
            [
                'id'                => 9,
                'title'             => 'delete_client',
                'description_en'    => 'Delete Client',
                'description_ar'    => 'حذف عميل',
                'admin_menu_id'     => 3,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
            // Users
            [
                'id'                => 10,
                'title'             => 'view_users',
                'description_en'    => 'View Users',
                'description_ar'    => 'عرض المستخدمين',
                'admin_menu_id'     => 5,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
            [
                'id'                => 11,
                'title'             => 'create_user',
                'description_en'    => 'Create User',
                'description_ar'    => 'إنشاء مستخدم',
                'admin_menu_id'     => 5,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
            [
                'id'                => 12,
                'title'             => 'edit_user',
                'description_en'    => 'Edit User',
                'description_ar'    => 'تعديل مستخدم',
                'admin_menu_id'     => 5,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
            [
                'id'                => 13,
                'title'             => 'delete_user',
                'description_en'    => 'Delete User',
                'description_ar'    => 'حذف مستخدم',
                'admin_menu_id'     => 5,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
            // Admin Groups
            [
                'id'                => 14,
                'title'             => 'view_admin_groups',
                'description_en'    => 'View Admin Groups',
                'description_ar'    => 'عرض مجموعات الإدارة',
                'admin_menu_id'     => 5,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
            [
                'id'                => 15,
                'title'             => 'manage_admin_groups',
                'description_en'    => 'Manage Admin Groups',
                'description_ar'    => 'إدارة مجموعات الإدارة',
                'admin_menu_id'     => 5,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
            // Reports
            [
                'id'                => 16,
                'title'             => 'view_reports',
                'description_en'    => 'View Reports',
                'description_ar'    => 'عرض التقارير',
                'admin_menu_id'     => 4,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
            [
                'id'                => 17,
                'title'             => 'export_reports',
                'description_en'    => 'Export Reports',
                'description_ar'    => 'تصدير التقارير',
                'admin_menu_id'     => 4,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
            // Permissions
            [
                'id'                => 18,
                'title'             => 'manage_permissions',
                'description_en'    => 'Manage Permissions',
                'description_ar'    => 'إدارة الصلاحيات',
                'admin_menu_id'     => 5,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
            // Installments
            [
                'id'                => 19,
                'title'             => 'create_installment',
                'description_en'    => 'Create Installment Plan',
                'description_ar'    => 'إنشاء خطة أقساط',
                'admin_menu_id'     => 2,
                'admin_sub_menu_id' => null,
                'parent_id'         => null,
                'is_parent'         => false,
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('admin_permissions')->insert(array_merge($permission, [
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]));
        }

        // 5. Assign all permissions to Super Admin (group id 1)
        foreach ($permissions as $permission) {
            DB::table('admin_group_permissions')->insert([
                'admin_group_id'       => 1,
                'admin_permission_id'  => $permission['id'],
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }

        // 6. Assign permissions to Accountant (group id 3)
        $accountantPermissionIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 16, 17, 19];
        foreach ($accountantPermissionIds as $permissionId) {
            DB::table('admin_group_permissions')->insert([
                'admin_group_id'       => 3,
                'admin_permission_id'  => $permissionId,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }

        // 7. Assign permissions to Sales (group id 4)
        $salesPermissionIds = [1, 2, 3, 6, 7, 8, 9];
        foreach ($salesPermissionIds as $permissionId) {
            DB::table('admin_group_permissions')->insert([
                'admin_group_id'       => 4,
                'admin_permission_id'  => $permissionId,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }
    }
}
