<?php

namespace App\Repository\Admin\InstallmentInterestTier;

use App\Models\InstallmentInterestTier;
use Illuminate\Database\Eloquent\Collection;

interface InstallmentInterestTierInterface
{
    public function all(): Collection;

    public function upsert(int $numberOfInstallments, float $interestRate): InstallmentInterestTier;

    public function delete(InstallmentInterestTier $tier): bool;
}
