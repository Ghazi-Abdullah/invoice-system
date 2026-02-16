<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ClientReportExport implements FromArray, WithHeadings, WithMapping, WithStyles, WithEvents, WithTitle, ShouldAutoSize
{
    protected $data;
    protected $filters;
    protected $stats;

    public function __construct(array $data, array $filters = [])
    {
        $this->data = $data;
        $this->filters = $filters;
        $this->stats = $data['stats'] ?? [];
    }

    public function array(): array
    {
        return $this->data['items'] ?? [];
    }

    public function headings(): array
    {
        return [
            'اسم العميل',
            'البريد الإلكتروني',
            'رقم الهاتف',
            'اسم الشركة',
            'عدد الفواتير',
            'إجمالي المبلغ',
            'المبلغ المدفوع',
            'المبلغ المستحق',
            'متوسط قيمة الفاتورة',
            'تاريخ الإنشاء',
            'آخر فاتورة',
            'حالة العميل'
        ];
    }

    public function map($client): array
    {
        $status = ($client['invoices_count'] ?? 0) > 0 ? 'نشط' : 'غير نشط';

        return [
            $client['name'] ?? 'غير محدد',
            $client['email'] ?? 'غير محدد',
            $client['phone'] ?? 'غير محدد',
            $client['company_name'] ?? 'غير محدد',
            $client['invoices_count'] ?? 0,
            $client['total_invoiced'] ?? 0,
            $client['total_paid'] ?? 0,
            $client['total_due'] ?? 0,
            $client['average_invoice'] ?? 0,
            $client['created_at'] ?? 'غير محدد',
            $client['last_invoice_date'] ?? 'لا يوجد',
            $status
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // تنسيق العنوان
        $sheet->getStyle('A1:L1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '27AE60']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // تنسيق جميع الخلايا
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A2:L' . $lastRow)
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        // تنسيق الأرقام
        $sheet->getStyle('F2:I' . $lastRow)
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        // إضافة حدود
        $sheet->getStyle('A1:L' . $lastRow)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $lastRow = $sheet->getHighestRow();

                // إضافة صف الإجماليات
                $summaryRow = $lastRow + 2;

                $sheet->setCellValue('A' . $summaryRow, 'إحصائيات التقرير:');
                $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);

                $sheet->setCellValue('A' . ($summaryRow + 1), 'إجمالي العملاء:');
                $sheet->setCellValue('B' . ($summaryRow + 1), $this->stats['total_clients'] ?? 0);

                $sheet->setCellValue('A' . ($summaryRow + 2), 'العملاء النشطين:');
                $sheet->setCellValue('B' . ($summaryRow + 2), $this->stats['active_clients'] ?? 0);

                $sheet->setCellValue('A' . ($summaryRow + 3), 'إجمالي الفواتير:');
                $sheet->setCellValue('B' . ($summaryRow + 3), $this->stats['total_invoices'] ?? 0);

                $sheet->setCellValue('A' . ($summaryRow + 4), 'إجمالي الإيرادات:');
                $sheet->setCellValue('B' . ($summaryRow + 4), number_format($this->stats['total_revenue'] ?? 0, 2));

                $sheet->setCellValue('A' . ($summaryRow + 5), 'نسبة التحصيل:');
                $sheet->setCellValue('B' . ($summaryRow + 5), ($this->stats['collection_rate'] ?? 0) . '%');

                // إضافة معلومات التقرير
                $infoRow = $summaryRow + 7;
                $sheet->setCellValue('A' . $infoRow, 'معلومات التقرير:');
                $sheet->getStyle('A' . $infoRow)->getFont()->setBold(true);

                $sheet->setCellValue('A' . ($infoRow + 1), 'تاريخ الإنشاء: ' . date('Y-m-d H:i:s'));
            }
        ];
    }

    public function title(): string
    {
        return 'تقرير العملاء';
    }
}
