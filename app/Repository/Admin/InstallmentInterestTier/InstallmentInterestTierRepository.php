<?php

namespace App\Repository\Admin\InstallmentInterestTier;

use App\Models\InstallmentInterestTier;
use Illuminate\Database\Eloquent\Collection;

class InstallmentInterestTierRepository implements InstallmentInterestTierInterface
{
    public function all(): Collection
    {
        return InstallmentInterestTier::orderBy('number_of_installments')->get();
    }

    public function upsert(int $numberOfInstallments, float $interestRate): InstallmentInterestTier
    {
        return InstallmentInterestTier::updateOrCreate(
            ['number_of_installments' => $numberOfInstallments],
            ['interest_rate' => $interestRate, 'is_active' => true]
        );
    }

    public function delete(InstallmentInterestTier $tier): bool
    {
        return (bool) $tier->delete();
    }
}
