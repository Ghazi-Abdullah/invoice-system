<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        // الحصول على الأدوار
        $adminRole = Role::where('name', 'admin')->first();
        $userRole = Role::where('name', 'user')->first();

        if (!$adminRole || !$userRole) {
            $this->command->error('❌ الأدوار غير موجودة! قم بتشغيل PermissionSeeder أولاً.');
            return;
        }

        // إعطاء دور admin للمستخدم الأول
        $firstUser = User::first();
        if ($firstUser) {
            $firstUser->roles()->sync([$adminRole->id]);
            $this->command->info("✅ تم إعطاء دور 'admin' للمستخدم: {$firstUser->email}");
        }

        // إعطاء دور user لجميع المستخدمين الآخرين
        $otherUsers = User::where('id', '>', 1)->get();
        foreach ($otherUsers as $user) {
            if (!$user->roles()->exists()) {
                $user->roles()->sync([$userRole->id]);
                $this->command->info("✅ تم إعطاء دور 'user' للمستخدم: {$user->email}");
            }
        }

        $this->command->info('🎉 تم تعيين الأدوار لجميع المستخدمين!');
    }
}
