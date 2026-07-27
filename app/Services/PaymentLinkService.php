<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PaymentLink;
use App\Constants\Constants;
use Illuminate\Support\Facades\Log;

class PaymentLinkService
{
    public function createPaymentLink(Invoice $invoice, array $options = []): PaymentLink
    {
        $expiresIn = $options['expires_in_days'] ?? 7;
        $amount = $options['amount'] ?? $invoice->total;
        PaymentLink::where('invoice_id', $invoice->id)->where('status', 'active')->update(['status' => 'cancelled']);
        return PaymentLink::create([
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'amount' => $amount,
            'currency' => $invoice->currency ?? Constants::CURRENCY_SAR,
            'expires_at' => now()->addDays($expiresIn),
            'metadata' => ['invoice_number' => $invoice->invoice_number, 'client_name' => $invoice->client->name ?? null, 'created_by' => auth()->id()],
        ]);
    }

    public function createInstallmentPaymentLink(Invoice $invoice, int $installmentNumber): PaymentLink
    {
        $plan = $invoice->installmentPlan;
        if (!$plan) throw new \InvalidArgumentException('Invoice does not have an installment plan');
        $installment = $plan->installments()->where('installment_number', $installmentNumber)->where('status', 'pending')->first();
        if (!$installment) throw new \InvalidArgumentException('Installment not found or already paid');
        return $this->createPaymentLink($invoice, ['amount' => $installment->amount, 'expires_in_days' => 3]);
    }

    public function validateLink(string $token): ?PaymentLink
    {
        $link = PaymentLink::where('token', $token)->first();
        if (!$link) return null;
        if (!$link->isActive()) {
            if ($link->status === 'active' && $link->isExpired()) $link->markAsExpired();
            return null;
        }
        return $link;
    }

    public function processPayment(PaymentLink $link, array $paymentData): void
    {
        $link->markAsPaid($paymentData['payment_method'] ?? 'card');
        if ($link->invoice->installmentPlan) {
            $installment = $link->invoice->installmentPlan->installments()->where('status', 'pending')->orderBy('installment_number')->first();
            if ($installment) $installment->markAsPaid($paymentData['payment_method'] ?? 'card');
        } else {
            $link->invoice->markAsPaid();
        }
    }

    public function sendLinkToClient(PaymentLink $link, string $method = 'email'): bool
    {
        try {
            $link->update(['sent_via' => $method, 'sent_at' => now()]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send payment link', ['link_id' => $link->id, 'method' => $method, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
