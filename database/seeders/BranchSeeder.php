<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * ينشئ 3 فروع تجريبية ويربط كل المستخدمين الحاليين بها،
     * مع تعيين الفرع الرئيسي كافتراضي لكل مستخدم.
     *
     * تشغيل يدوي فقط:
     *   php artisan db:seed --class=BranchSeeder
     */
    public function run(): void
    {
        $branches = [
            ['name' => 'الفرع الرئيسي', 'name_en' => 'Main Branch',    'code' => 'MAIN', 'city' => 'الرياض', 'is_active' => true, 'is_main' => true],
            ['name' => 'فرع جدة',        'name_en' => 'Jeddah Branch',  'code' => 'JED',  'city' => 'جدة',    'is_active' => true, 'is_main' => false],
            ['name' => 'فرع الدمام',     'name_en' => 'Dammam Branch',  'code' => 'DMM',  'city' => 'الدمام', 'is_active' => true, 'is_main' => false],
        ];

        $createdBranches = collect($branches)->map(
            fn (array $branch) => Branch::firstOrCreate(['code' => $branch['code']], $branch)
        );

        $mainBranch = $createdBranches->firstWhere('is_main', true) ?? $createdBranches->first();

        User::all()->each(function (User $user) use ($createdBranches, $mainBranch) {
            $syncData = $createdBranches->mapWithKeys(fn ($branch) => [
                $branch->id => ['is_default' => $branch->id === $mainBranch->id],
            ]);
            $user->branches()->syncWithoutDetaching($syncData);
        });

        $this->command?->info('تم إنشاء ' . $createdBranches->count() . ' فروع تجريبية وربطها بـ ' . User::count() . ' مستخدم.');
    }
}