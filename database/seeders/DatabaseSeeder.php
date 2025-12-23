<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            PermissionSystemSeeder::class,
            /*AdminGroupsSeeder::class,
            AdminMenusSeeder::class,
            AdminPermissionsSeeder::class,
            AdminSubMenusSeeder::class,
            UsersSeeder::class,
            ClientsSeeder::class,
            SampleInvoicesSeeder::class,*/
        ]);
    }
}
