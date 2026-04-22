<?php

namespace App\Repository\Admin\Payment;

use App\Models\Invoice;
use Illuminate\Http\Request;

interface PaymentInterface
{
    public function createCheckoutSession(Invoice $invoice): array;
    public function handleWebhook(string $payload, ?string $sigHeader): array;
    public function getPayment(int $id): array;
    public function getPayments(Request $request): array;
    public function refundPayment(int $id, ?float $amount): array;
}
