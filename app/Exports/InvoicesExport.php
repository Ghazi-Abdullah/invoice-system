<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class InvoicesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Invoice::with(['client', 'createdBy']);

        // Apply filters
        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['date_from']) && isset($this->filters['date_to'])) {
            $query->whereBetween('invoice_date', [
                $this->filters['date_from'],
                $this->filters['date_to']
            ]);
        }

        if (isset($this->filters['client_id'])) {
            $query->where('client_id', $this->filters['client_id']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Invoice Number',
            'Client',
            'Invoice Date',
            'Due Date',
            'Status',
            'Subtotal',
            'Tax Amount',
            'Discount Amount',
            'Total',
            'Currency',
            'Created By',
            'Created At'
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->client ? $invoice->client->name : 'N/A',
            $invoice->invoice_date->format('Y-m-d'),
            $invoice->due_date->format('Y-m-d'),
            ucfirst($invoice->status),
            $invoice->subtotal,
            $invoice->tax_amount,
            $invoice->discount_amount,
            $invoice->total,
            $invoice->currency,
            $invoice->createdBy ? $invoice->createdBy->name : 'N/A',
            $invoice->created_at->format('Y-m-d H:i:s')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],

            // Style the header row
            'A1:L1' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE0E0E0']
                ]
            ],

            // Format currency columns
            'F' => ['numberFormat' => ['formatCode' => '#,##0.00']],
            'G' => ['numberFormat' => ['formatCode' => '#,##0.00']],
            'H' => ['numberFormat' => ['formatCode' => '#,##0.00']],
            'I' => ['numberFormat' => ['formatCode' => '#,##0.00']],
        ];
    }
}
