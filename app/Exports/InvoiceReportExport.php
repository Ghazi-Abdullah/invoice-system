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

class InvoiceReportExport implements FromArray, WithHeadings, WithMapping, WithStyles, WithEvents, WithTitle, ShouldAutoSize
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
            'رقم الفاتورة',
            'اسم العميل',
            'بريد العميل',
            'تاريخ الإصدار',
            'تاريخ الاستحقاق',
            'المبلغ الإجمالي',
            'المبلغ الأساسي',
            'مبلغ الضريبة',
            'مبلغ الخصم',
            'حالة الفاتورة',
            'العملة',
            'تاريخ الإنشاء',
            'تاريخ الدفع',
            'ملاحظات'
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice['invoice_number'] ?? 'غير محدد',
            $invoice['client_name'] ?? 'غير محدد',
            $invoice['client_email'] ?? 'غير محدد',
            $invoice['issue_date'] ?? 'غير محدد',
            $invoice['due_date'] ?? 'غير محدد',
            $invoice['total_amount'] ?? 0,
            $invoice['subtotal'] ?? 0,
            $invoice['tax_amount'] ?? 0,
            $invoice['discount_amount'] ?? 0,
            $this->getStatusArabic($invoice['status'] ?? ''),
            $invoice['currency'] ?? 'SAR',
            $invoice['created_at'] ?? 'غير محدد',
            $invoice['paid_at'] ?? 'لم يتم الدفع',
            $invoice['notes'] ?? ''
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // تنسيق العنوان
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2C3E50']
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
        $sheet->getStyle('A2:N' . $lastRow)
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        // تنسيق الأرقام كعملة
        $sheet->getStyle('F2:I' . $lastRow)
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        // إضافة حدود للجدول
        $sheet->getStyle('A1:N' . $lastRow)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // جعل العناوين ملائمة للنص العربي
        $sheet->getStyle('A1:N1')
            ->getAlignment()
            ->setWrapText(true);

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

                $sheet->setCellValue('A' . ($summaryRow + 1), 'عدد الفواتير:');
                $sheet->setCellValue('B' . ($summaryRow + 1), $this->stats['total_invoices'] ?? 0);

                $sheet->setCellValue('A' . ($summaryRow + 2), 'إجمالي المبلغ:');
                $sheet->setCellValue('B' . ($summaryRow + 2), number_format($this->stats['total_amount'] ?? 0, 2));

                $sheet->setCellValue('A' . ($summaryRow + 3), 'المبلغ المدفوع:');
                $sheet->setCellValue('B' . ($summaryRow + 3), number_format($this->stats['total_paid'] ?? 0, 2));

                $sheet->setCellValue('A' . ($summaryRow + 4), 'المبلغ المستحق:');
                $sheet->setCellValue('B' . ($summaryRow + 4), number_format($this->stats['total_due'] ?? 0, 2));

                // تنسيق صفوف الإحصائيات
                $statsRange = 'A' . $summaryRow . ':B' . ($summaryRow + 4);
                $sheet->getStyle($statsRange)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F2F2F2']
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC']
                        ]
                    ]
                ]);

                // إضافة معلومات التقرير
                $infoRow = $summaryRow + 6;
                $sheet->setCellValue('A' . $infoRow, 'معلومات التقرير:');
                $sheet->getStyle('A' . $infoRow)->getFont()->setBold(true)->setSize(12);

                $sheet->setCellValue('A' . ($infoRow + 1), 'تاريخ الإنشاء: ' . date('Y-m-d H:i:s'));

                $currentRow = $infoRow + 2;
                if (!empty($this->filters['start_date'])) {
                    $sheet->setCellValue('A' . $currentRow, 'تاريخ البدء: ' . $this->filters['start_date']);
                    $currentRow++;
                }

                if (!empty($this->filters['end_date'])) {
                    $sheet->setCellValue('A' . $currentRow, 'تاريخ النهاية: ' . $this->filters['end_date']);
                    $currentRow++;
                }

                if (!empty($this->filters['status'])) {
                    $sheet->setCellValue('A' . $currentRow, 'حالة الفاتورة: ' . $this->getStatusArabic($this->filters['status']));
                    $currentRow++;
                }

                if (!empty($this->filters['client_id'])) {
                    $sheet->setCellValue('A' . $currentRow, 'رقم العميل: ' . $this->filters['client_id']);
                    $currentRow++;
                }

                // إضافة تذييل
                $footerRow = $currentRow + 2;
                $sheet->setCellValue('A' . $footerRow, 'ملاحظة: تم إنشاء هذا التقرير بواسطة نظام الفواتير');
                $sheet->getStyle('A' . $footerRow)->getFont()->setItalic(true);
            }
        ];
    }

    public function title(): string
    {
        return 'تقرير الفواتير';
    }

    private function getStatusArabic(string $status): string
    {
        $statusMap = [
            'draft' => 'مسودة',
            'sent' => 'مرسلة',
            'paid' => 'مدفوعة',
            'overdue' => 'متأخرة',
            'cancelled' => 'ملغية',
            'partial' => 'مدفوعة جزئياً'
        ];

        return $statusMap[$status] ?? $status;
    }
}
