<?php

namespace Database\Seeders;

use App\Models\InstallmentInterestTier;
use Illuminate\Database\Seeder;

class InstallmentInterestTierSeeder extends Seeder
{
    /**
     * قيم افتراضية أولية فقط — تُدار لاحقاً بالكامل من واجهة الإعدادات
     * (إضافة/تعديل/حذف)، هذي بس نقطة انطلاق معقولة.
     */
    public function run()
    {
        $defaults = [
            ['number_of_installments' => 3,  'interest_rate' => 5.00],
            ['number_of_installments' => 6,  'interest_rate' => 10.00],
            ['number_of_installments' => 12, 'interest_rate' => 15.00],
        ];

        foreach ($defaults as $tier) {
            InstallmentInterestTier::updateOrCreate(
                ['number_of_installments' => $tier['number_of_installments']],
                ['interest_rate' => $tier['interest_rate'], 'is_active' => true]
            );
        }
    }
}
