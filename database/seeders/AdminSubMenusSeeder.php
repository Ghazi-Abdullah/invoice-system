<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminSubMenusSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::table('admin_sub_menus')->truncate();
        DB::table('admin_sub_menus')->insert([
            // Clients Submenus
            [
                'admin_menu_id' => 2,
                'title_en' => 'All Clients',
                'title_ar' => 'جميع العملاء',
                'link' => '/clients',
                'sort_order' => 1
            ],
            [
                'admin_menu_id' => 2,
                'title_en' => 'Add Client',
                'title_ar' => 'إضافة عميل',
                'link' => '/clients/create',
                'sort_order' => 2
            ],

            // Invoices Submenus
            [
                'admin_menu_id' => 3,
                'title_en' => 'All Invoices',
                'title_ar' => 'جميع الفواتير',
                'link' => '/invoices',
                'sort_order' => 1
            ],
            [
                'admin_menu_id' => 3,
                'title_en' => 'Create Invoice',
                'title_ar' => 'إنشاء فاتورة',
                'link' => '/invoices/create',
                'sort_order' => 2
            ],

            // Reports Submenus
            [
                'admin_menu_id' => 4,
                'title_en' => 'Sales Report',
                'title_ar' => 'تقرير المبيعات',
                'link' => '/reports/sales',
                'sort_order' => 1
            ],

            // Administration Submenus
            [
                'admin_menu_id' => 5,
                'title_en' => 'User Groups',
                'title_ar' => 'مجموعات المستخدمين',
                'link' => '/admin/groups',
                'sort_order' => 1
            ],
            [
                'admin_menu_id' => 5,
                'title_en' => 'Users',
                'title_ar' => 'المستخدمون',
                'link' => '/admin/users',
                'sort_order' => 2
            ],
            [
                'admin_menu_id' => 5,
                'title_en' => 'Permissions',
                'title_ar' => 'الصلاحيات',
                'link' => '/admin/permissions',
                'sort_order' => 3
            ],
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
