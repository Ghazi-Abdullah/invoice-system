<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminGroupsSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::table('admin_groups')->truncate();
        DB::table('admin_groups')->insert([
            [
                'title_en' => 'Administrator',
                'title_ar' => 'مدير',
                'is_active' => true,
            ],
            [
                'title_en' => 'Accountant',
                'title_ar' => 'محاسب',
                'is_active' => true,
            ],
            [
                'title_en' => 'Sales',
                'title_ar' => 'مبيعات',
                'is_active' => true,
            ],
            [
                'title_en' => 'View Only',
                'title_ar' => 'عرض فقط',
                'is_active' => true,
            ],
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
