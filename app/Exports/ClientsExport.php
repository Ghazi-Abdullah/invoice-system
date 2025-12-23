<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ClientsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Client::query();

        // Apply filters
        if (isset($this->filters['is_active'])) {
            $query->where('is_active', $this->filters['is_active']);
        }

        if (isset($this->filters['search'])) {
            $query->search($this->filters['search']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Phone',
            'Company',
            'Address',
            'Tax Number',
            'Payment Terms',
            'Currency',
            'Status',
            'Created At',
            'Total Invoiced',
            'Total Paid',
            'Total Due'
        ];
    }

    public function map($client): array
    {
        $totalInvoiced = $client->invoices()->sum('total');
        $totalPaid = $client->invoices()->where('status', 'paid')->sum('total');
        $totalDue = $totalInvoiced - $totalPaid;

        return [
            $client->name,
            $client->email,
            $client->phone,
            $client->company_name,
            $client->address,
            $client->tax_number,
            $client->payment_terms,
            $client->currency,
            $client->is_active ? 'Active' : 'Inactive',
            $client->created_at->format('Y-m-d H:i:s'),
            $totalInvoiced,
            $totalPaid,
            $totalDue
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],

            'A1:M1' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE0E0E0']
                ]
            ],

            'K' => ['numberFormat' => ['formatCode' => '#,##0.00']],
            'L' => ['numberFormat' => ['formatCode' => '#,##0.00']],
            'M' => ['numberFormat' => ['formatCode' => '#,##0.00']],
        ];
    }
}
