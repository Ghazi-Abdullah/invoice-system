<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


class UserSeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء المستخدمين
        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@invoice.com',
                'password' => Hash::make('password'),
                'admin_group_id' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Accountant',
                'email' => 'accountant@invoice.com',
                'password' => Hash::make('password'),
                'admin_group_id' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Sales',
                'email' => 'sales@invoice.com',
                'password' => Hash::make('password'),
                'admin_group_id' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        // تعيين الصلاحيات للمجموعات
        $this->assignPermissions();
    }

    private function assignPermissions(): void
    {
        // Administrator has all permissions
        for ($i = 1; $i <= 20; $i++) {
            DB::table('admin_group_permissions')->insert([
                'admin_group_id' => 1,
                'permission_id' => $i
            ]);
        }

        // Accountant permissions
        $accountantPermissions = [1, 2, 3, 7, 8, 12, 13, 14, 15, 16];
        foreach ($accountantPermissions as $permissionId) {
            DB::table('admin_group_permissions')->insert([
                'admin_group_id' => 2,
                'permission_id' => $permissionId
            ]);
        }

        // Sales permissions
        $salesPermissions = [1, 2, 3, 4, 5, 7, 8, 9, 10, 12, 13];
        foreach ($salesPermissions as $permissionId) {
            DB::table('admin_group_permissions')->insert([
                'admin_group_id' => 3,
                'permission_id' => $permissionId
            ]);
        }
    }
}
