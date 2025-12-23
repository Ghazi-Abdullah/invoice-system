<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Client;
use App\Constants\Constants;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function generateInvoiceNumber()
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "INV-{$year}{$month}-";

        $lastInvoice = Invoice::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1001;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function calculateInvoiceTotals($items, $taxRate = 0, $discountAmount = 0)
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $itemTotal = $item['quantity'] * $item['unit_price'];
            $subtotal += $itemTotal;
        }

        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount - $discountAmount;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total
        ];
    }

    public function generatePDF(Invoice $invoice)
    {
        $data = [
            'invoice' => $invoice->load(['client', 'items']),
            'settings' => [
                'company_name' => config('invoice.company.name', 'Your Company'),
                'company_address' => config('invoice.company.address', ''),
                'company_logo' => config('invoice.company.logo', ''),
                'company_phone' => config('invoice.company.phone', ''),
                'company_email' => config('invoice.company.email', ''),
                'company_website' => config('invoice.company.website', ''),
            ]
        ];

        $pdf = PDF::loadView('pdf.invoice', $data);

        return $pdf;
    }

    public function savePDF(Invoice $invoice)
    {
        $pdf = $this->generatePDF($invoice);

        $fileName = 'invoice-' . $invoice->invoice_number . '.pdf';
        $filePath = 'invoices/' . $fileName;

        Storage::disk('public')->put($filePath, $pdf->output());

        return [
            'file_path' => $filePath,
            'file_url' => Storage::disk('public')->url($filePath),
            'file_name' => $fileName
        ];
    }

    public function sendInvoiceEmail(Invoice $invoice, $email = null)
    {
        // Get client email if not provided
        if (!$email && $invoice->client) {
            $email = $invoice->client->email;
        }

        if (!$email) {
            return false;
        }

        // Generate PDF
        $pdf = $this->generatePDF($invoice);

        // TODO: Implement email sending logic
        // This would typically use Laravel's Mail facade

        return true;
    }

    public function getInvoiceStats($period = 'month')
    {
        $stats = [];

        switch ($period) {
            case 'day':
                $dateFormat = 'Y-m-d';
                $dateSub = '1 DAY';
                break;
            case 'week':
                $dateFormat = 'Y-W';
                $dateSub = '1 WEEK';
                break;
            case 'month':
            default:
                $dateFormat = 'Y-m';
                $dateSub = '1 MONTH';
                break;
        }

        // Get invoice stats for the period
        $invoices = Invoice::selectRaw("
            DATE_FORMAT(invoice_date, '{$dateFormat}') as period,
            COUNT(*) as count,
            SUM(total) as total,
            SUM(CASE WHEN status = 'paid' THEN total ELSE 0 END) as paid_total,
            SUM(CASE WHEN status = 'overdue' THEN total ELSE 0 END) as overdue_total
        ")
        ->where('invoice_date', '>=', now()->sub($dateSub))
        ->groupBy('period')
        ->orderBy('period', 'asc')
        ->get();

        // Get client stats
        $clients = Client::selectRaw("
            COUNT(*) as total_clients,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL {$dateSub}) THEN 1 END) as new_clients
        ")->first();

        return [
            'invoices' => $invoices,
            'clients' => $clients,
            'period' => $period
        ];
    }
}
