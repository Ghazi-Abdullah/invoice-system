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
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Conditional;

class OverdueReportExport implements FromArray, WithHeadings, WithMapping, WithStyles, WithEvents, WithTitle, ShouldAutoSize
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
            'المبلغ المدفوع',
            'المبلغ المستحق',
            'حالة الفاتورة',
            'أيام التأخير',
            'تم التواصل',
            'آخر تذكير'
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
            $invoice['paid_amount'] ?? 0,
            $invoice['due_amount'] ?? 0,
            $this->getStatusArabic($invoice['status'] ?? ''),
            $invoice['days_overdue'] ?? 0,
            $invoice['contacted'] ?? 'لا',
            $invoice['last_reminder'] ?? 'لم يتم'
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
                'startColor' => ['rgb' => 'E74C3C']
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
        $sheet->getStyle('F2:H' . $lastRow)
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

                // إضافة تنسيق مشروط لأيام التأخير
                $conditionalStyles = [];

                // أكثر من 30 يوم - لون أحمر داكن
                $redStyle = new Conditional();
                $redStyle->setConditionType(Conditional::CONDITION_CELLIS);
                $redStyle->setOperatorType(Conditional::OPERATOR_GREATERTHAN);
                $redStyle->addCondition('30');
                $redStyle->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
                $redStyle->getStyle()->getFill()->getStartColor()->setARGB('FFC0392B');
                $redStyle->getStyle()->getFont()->getColor()->setARGB('FFFFFFFF');
                $conditionalStyles[] = $redStyle;

                // من 15 إلى 30 يوم - لون برتقالي
                $orangeStyle = new Conditional();
                $orangeStyle->setConditionType(Conditional::CONDITION_CELLIS);
                $orangeStyle->setOperatorType(Conditional::OPERATOR_BETWEEN);
                $orangeStyle->addCondition('15');
                $orangeStyle->addCondition('30');
                $orangeStyle->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
                $orangeStyle->getStyle()->getFill()->getStartColor()->setARGB('FFE67E22');
                $orangeStyle->getStyle()->getFont()->getColor()->setARGB('FFFFFFFF');
                $conditionalStyles[] = $orangeStyle;

                // من 7 إلى 14 يوم - لون أصفر
                $yellowStyle = new Conditional();
                $yellowStyle->setConditionType(Conditional::CONDITION_CELLIS);
                $yellowStyle->setOperatorType(Conditional::OPERATOR_BETWEEN);
                $yellowStyle->addCondition('7');
                $yellowStyle->addCondition('14');
                $yellowStyle->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
                $yellowStyle->getStyle()->getFill()->getStartColor()->setARGB('FFF1C40F');
                $yellowStyle->getStyle()->getFont()->getColor()->setARGB('FF2C3E50');
                $conditionalStyles[] = $yellowStyle;

                // تطبيق التنسيق المشروط على عمود أيام التأخير (العمود J)
                $sheet->getStyle('J2:J' . $lastRow)->setConditionalStyles($conditionalStyles);

                // إضافة صف الإجماليات
                $summaryRow = $lastRow + 2;

                $sheet->setCellValue('A' . $summaryRow, 'إحصائيات التقرير:');
                $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true)->setSize(14);

                $sheet->setCellValue('A' . ($summaryRow + 1), 'عدد الفواتير المتأخرة:');
                $sheet->setCellValue('B' . ($summaryRow + 1), $this->stats['total_overdue'] ?? 0);

                $sheet->setCellValue('A' . ($summaryRow + 2), 'إجمالي المبلغ المتأخر:');
                $sheet->setCellValue('B' . ($summaryRow + 2), number_format($this->stats['total_amount'] ?? 0, 2));

                $sheet->setCellValue('A' . ($summaryRow + 3), 'المبلغ المدفوع:');
                $sheet->setCellValue('B' . ($summaryRow + 3), number_format($this->stats['paid_amount'] ?? 0, 2));

                $sheet->setCellValue('A' . ($summaryRow + 4), 'المبلغ المستحق:');
                $sheet->setCellValue('B' . ($summaryRow + 4), number_format($this->stats['due_amount'] ?? 0, 2));

                $sheet->setCellValue('A' . ($summaryRow + 5), 'متوسط أيام التأخير:');
                $sheet->setCellValue('B' . ($summaryRow + 5), round($this->stats['average_days_overdue'] ?? 0, 1) . ' يوم');

                $sheet->setCellValue('A' . ($summaryRow + 6), 'أقصى أيام تأخير:');
                $sheet->setCellValue('B' . ($summaryRow + 6), ($this->stats['max_days_overdue'] ?? 0) . ' يوم');

                $sheet->setCellValue('A' . ($summaryRow + 7), 'الفواتير التي تم التواصل معها:');
                $sheet->setCellValue('B' . ($summaryRow + 7), ($this->stats['contacted_invoices'] ?? 0) . ' فاتورة');

                // تنسيق صفوف الإحصائيات
                $statsRange = 'A' . $summaryRow . ':B' . ($summaryRow + 7);
                $sheet->getStyle($statsRange)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FDEBD0']
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E74C3C']
                        ]
                    ]
                ]);

                // إضافة معلومات التقرير
                $infoRow = $summaryRow + 9;
                $sheet->setCellValue('A' . $infoRow, 'معلومات التقرير:');
                $sheet->getStyle('A' . $infoRow)->getFont()->setBold(true)->setSize(12);

                $sheet->setCellValue('A' . ($infoRow + 1), 'تاريخ الإنشاء: ' . date('Y-m-d H:i:s'));
                $sheet->setCellValue('A' . ($infoRow + 2), 'ملاحظة:');
                $sheet->setCellValue('A' . ($infoRow + 3), '• الفواتير باللون الأحمر تأخرت أكثر من 30 يوم');
                $sheet->setCellValue('A' . ($infoRow + 4), '• الفواتير باللون البرتقالي تأخرت من 15-30 يوم');
                $sheet->setCellValue('A' . ($infoRow + 5), '• الفواتير باللون الأصفر تأخرت من 7-14 يوم');

                if (!empty($this->filters['start_date'])) {
                    $sheet->setCellValue('A' . ($infoRow + 6), 'تاريخ البدء: ' . $this->filters['start_date']);
                }

                if (!empty($this->filters['end_date'])) {
                    $sheet->setCellValue('A' . ($infoRow + 7), 'تاريخ النهاية: ' . $this->filters['end_date']);
                }

                // إضافة نصائح للمتابعة
                $tipsRow = $infoRow + 9;
                $sheet->setCellValue('A' . $tipsRow, 'نصائح للمتابعة:');
                $sheet->getStyle('A' . $tipsRow)->getFont()->setBold(true)->setColor(new Color(Color::COLOR_RED));

                $sheet->setCellValue('A' . ($tipsRow + 1), '1. أولوية المتابعة للفواتير التي تأخرت أكثر من 30 يوم');
                $sheet->setCellValue('A' . ($tipsRow + 2), '2. إرسال تذكيرات للعملاء الذين لم يتم التواصل معهم');
                $sheet->setCellValue('A' . ($tipsRow + 3), '3. اقتراح خطط سداد للفواتير كبيرة القيمة');
                $sheet->setCellValue('A' . ($tipsRow + 4), '4. مراجعة شروط السداد مع العملاء المتكررين التأخير');
                $sheet->setCellValue('A' . ($tipsRow + 5), '5. متابعة الفواتير التي لم يتم إرسال تذكير لها خلال أسبوع');
            }
        ];
    }

    public function title(): string
    {
        return 'تقرير المتأخرات';
    }

    private function getStatusArabic(string $status): string
    {
        $statusMap = [
            'draft' => 'مسودة',
            'sent' => 'مرسلة',
            'paid' => 'مدفوعة',
            'overdue' => 'متأخرة',
            'partial' => 'مدفوعة جزئياً'
        ];

        return $statusMap[$status] ?? $status;
    }
}
