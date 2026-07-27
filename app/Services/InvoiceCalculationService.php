<?php

namespace App\Services;

use App\Models\Invoice;
use App\Constants\Constants;

class InvoiceCalculationService
{
    public static function calculateInvoiceTotals(Invoice $invoice, array $items = []): array
    {
        $subtotal = 0; $taxAmount = 0;
        foreach ($items as $item) {
            $itemSubtotal = (float)($item['quantity'] ?? 1) * (float)($item['unit_price'] ?? 0);
            $itemTax = $itemSubtotal * ((float)($item['tax_rate'] ?? 0) / 100);
            $subtotal += $itemSubtotal; $taxAmount += $itemTax;
        }
        $discountAmount = (float)($invoice->discount_amount ?? 0);
        $total = $subtotal + $taxAmount - $discountAmount;
        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'discount_amount' => round($discountAmount, 2),
            'total' => max(0, round($total, 2)),
        ];
    }

    public static function calculateInstallments(float $totalAmount, int $numberOfInstallments, float $interestRate = 0): array
    {
        $interestAmount = $totalAmount * ($interestRate / 100);
        $totalWithInterest = $totalAmount + $interestAmount;
        $baseAmount = round($totalWithInterest / $numberOfInstallments, 2);
        $installments = []; $remainingAmount = $totalWithInterest;
        for ($i = 1; $i <= $numberOfInstallments; $i++) {
            $amount = ($i === $numberOfInstallments) ? round($remainingAmount, 2) : $baseAmount;
            $remainingAmount -= $amount;
            $installments[] = ['installment_number' => $i, 'amount' => $amount, 'status' => 'pending'];
        }
        return [
            'original_amount' => round($totalAmount, 2),
            'interest_amount' => round($interestAmount, 2),
            'total_amount' => round($totalWithInterest, 2),
            'installments' => $installments,
        ];
    }

    public static function redistributeInstallments(array $remainingInstallments, float $totalAmount): array
    {
        $count = count($remainingInstallments);
        if ($count === 0) return [];
        $newBaseAmount = round($totalAmount / $count, 2);
        $remaining = $totalAmount; $result = [];
        foreach ($remainingInstallments as $index => $installment) {
            $isLast = ($index === $count - 1);
            $amount = $isLast ? round($remaining, 2) : $newBaseAmount;
            $remaining -= $amount;
            $result[] = array_merge($installment, ['installment_number' => $index + 1, 'amount' => $amount]);
        }
        return $result;
    }
}
