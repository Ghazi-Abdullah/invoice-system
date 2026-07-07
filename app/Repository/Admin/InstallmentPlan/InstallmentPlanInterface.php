<?php

namespace App\Repository\Admin\InstallmentPlan;

use App\Models\Invoice;
use App\Models\InstallmentPlan;

interface InstallmentPlanInterface
{
    public function findForInvoice(Invoice $invoice): ?InstallmentPlan;

    public function create(Invoice $invoice, array $data): InstallmentPlan;

    public function suggestInterestRate(int $numberOfInstallments): float;

    public function payInstallment(int $installmentId, string $paymentMethod = 'cash');

    public function cancel(InstallmentPlan $plan): InstallmentPlan;
}
