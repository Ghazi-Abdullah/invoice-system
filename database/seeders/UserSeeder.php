<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::table('users')->truncate();
        DB::table('users')->insert([
            [
                'name' => 'Admin',
                'email' => 'admin@invoice.com',
                'password' => Hash::make('password'),
                'admin_group_id' => 1,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Accountant',
                'email' => 'accountant@invoice.com',
                'password' => Hash::make('password'),
                'admin_group_id' => 2,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Sales',
                'email' => 'sales@invoice.com',
                'password' => Hash::make('password'),
                'admin_group_id' => 3,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        // Assign permissions to groups
        DB::table('admin_group_permissions')->truncate();
        DB::table('admin_group_permissions')->insert([
            // Administrator has all permissions
            ['admin_group_id' => 1, 'admin_permission_id' => 1],
            ['admin_group_id' => 1, 'admin_permission_id' => 2],
            ['admin_group_id' => 1, 'admin_permission_id' => 3],
            ['admin_group_id' => 1, 'admin_permission_id' => 4],
            ['admin_group_id' => 1, 'admin_permission_id' => 5],
            ['admin_group_id' => 1, 'admin_permission_id' => 6],
            ['admin_group_id' => 1, 'admin_permission_id' => 7],
            ['admin_group_id' => 1, 'admin_permission_id' => 8],
            ['admin_group_id' => 1, 'admin_permission_id' => 9],
            ['admin_group_id' => 1, 'admin_permission_id' => 10],
            ['admin_group_id' => 1, 'admin_permission_id' => 11],
            ['admin_group_id' => 1, 'admin_permission_id' => 12],
            ['admin_group_id' => 1, 'admin_permission_id' => 13],
            ['admin_group_id' => 1, 'admin_permission_id' => 14],
            ['admin_group_id' => 1, 'admin_permission_id' => 15],
            ['admin_group_id' => 1, 'admin_permission_id' => 16],
            ['admin_group_id' => 1, 'admin_permission_id' => 17],
            ['admin_group_id' => 1, 'admin_permission_id' => 18],
            ['admin_group_id' => 1, 'admin_permission_id' => 19],
            ['admin_group_id' => 1, 'admin_permission_id' => 20],

            // Accountant permissions
            ['admin_group_id' => 2, 'admin_permission_id' => 1],
            ['admin_group_id' => 2, 'admin_permission_id' => 2],
            ['admin_group_id' => 2, 'admin_permission_id' => 3],
            ['admin_group_id' => 2, 'admin_permission_id' => 7],
            ['admin_group_id' => 2, 'admin_permission_id' => 8],
            ['admin_group_id' => 2, 'admin_permission_id' => 12],
            ['admin_group_id' => 2, 'admin_permission_id' => 13],
            ['admin_group_id' => 2, 'admin_permission_id' => 14],
            ['admin_group_id' => 2, 'admin_permission_id' => 15],
            ['admin_group_id' => 2, 'admin_permission_id' => 16],

            // Sales permissions
            ['admin_group_id' => 3, 'admin_permission_id' => 1],
            ['admin_group_id' => 3, 'admin_permission_id' => 2],
            ['admin_group_id' => 3, 'admin_permission_id' => 3],
            ['admin_group_id' => 3, 'admin_permission_id' => 4],
            ['admin_group_id' => 3, 'admin_permission_id' => 5],
            ['admin_group_id' => 3, 'admin_permission_id' => 7],
            ['admin_group_id' => 3, 'admin_permission_id' => 8],
            ['admin_group_id' => 3, 'admin_permission_id' => 9],
            ['admin_group_id' => 3, 'admin_permission_id' => 10],
            ['admin_group_id' => 3, 'admin_permission_id' => 12],
            ['admin_group_id' => 3, 'admin_permission_id' => 13],
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
