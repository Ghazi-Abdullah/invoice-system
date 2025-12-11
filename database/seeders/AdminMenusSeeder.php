<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminMenusSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::table('admin_menus')->truncate();
        DB::table('admin_menus')->insert([
            [
                'title_en' => 'Dashboard',
                'title_ar' => 'لوحة القيادة',
                'link' => '/dashboard',
                'icon_class' => 'heroicons-outline:home',
                'sort_order' => 1
            ],
            [
                'title_en' => 'Clients',
                'title_ar' => 'العملاء',
                'link' => '',
                'icon_class' => 'heroicons-outline:user-group',
                'sort_order' => 2
            ],
            [
                'title_en' => 'Invoices',
                'title_ar' => 'الفواتير',
                'link' => '',
                'icon_class' => 'heroicons-outline:document-text',
                'sort_order' => 3
            ],
            [
                'title_en' => 'Reports',
                'title_ar' => 'التقارير',
                'link' => '',
                'icon_class' => 'heroicons-outline:chart-bar',
                'sort_order' => 4
            ],
            [
                'title_en' => 'Administration',
                'title_ar' => 'الإدارة',
                'link' => '',
                'icon_class' => 'heroicons-outline:cog',
                'sort_order' => 5
            ],
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
