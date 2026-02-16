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
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title as ChartTitle;

class RevenueReportExport implements FromArray, WithHeadings, WithMapping, WithStyles, WithEvents, WithTitle, ShouldAutoSize
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
            'الشهر',
            'عدد الفواتير',
            'إجمالي الإيرادات',
            'الإيرادات المحصلة',
            'الإيرادات المستحقة',
            'نسبة التحصيل %'
        ];
    }

    public function map($item): array
    {
        return [
            $item['month'] ?? 'غير محدد',
            $item['invoice_count'] ?? 0,
            $item['total_amount'] ?? 0,
            $item['paid_amount'] ?? 0,
            $item['due_amount'] ?? 0,
            $item['collection_rate'] ?? 0
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // تنسيق العنوان
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '8E44AD']
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
        $sheet->getStyle('A2:F' . $lastRow)
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        // تنسيق الأرقام
        $sheet->getStyle('C2:E' . $lastRow)
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        $sheet->getStyle('F2:F' . $lastRow)
            ->getNumberFormat()
            ->setFormatCode('0.00"%"');

        // إضافة حدود
        $sheet->getStyle('A1:F' . $lastRow)
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

                $sheet->setCellValue('A' . ($summaryRow + 1), 'إجمالي الإيرادات:');
                $sheet->setCellValue('B' . ($summaryRow + 1), number_format($this->stats['total_revenue'] ?? 0, 2));

                $sheet->setCellValue('A' . ($summaryRow + 2), 'الإيرادات المحصلة:');
                $sheet->setCellValue('B' . ($summaryRow + 2), number_format($this->stats['collected_revenue'] ?? 0, 2));

                $sheet->setCellValue('A' . ($summaryRow + 3), 'الإيرادات المستحقة:');
                $sheet->setCellValue('B' . ($summaryRow + 3), number_format($this->stats['outstanding_revenue'] ?? 0, 2));

                $sheet->setCellValue('A' . ($summaryRow + 4), 'نسبة التحصيل:');
                $sheet->setCellValue('B' . ($summaryRow + 4), ($this->stats['collection_rate'] ?? 0) . '%');

                $sheet->setCellValue('A' . ($summaryRow + 5), 'متوسط الإيرادات الشهري:');
                $sheet->setCellValue('B' . ($summaryRow + 5), number_format($this->stats['average_monthly_revenue'] ?? 0, 2));

                // إنشاء مخطط بياني إذا كان هناك بيانات
                if ($lastRow > 2) {
                    $this->createRevenueChart($sheet, $lastRow, $summaryRow);
                }

                // إضافة معلومات التقرير
                $infoRow = $summaryRow + 20; // بعد المخطط
                $sheet->setCellValue('A' . $infoRow, 'معلومات التقرير:');
                $sheet->getStyle('A' . $infoRow)->getFont()->setBold(true);

                $sheet->setCellValue('A' . ($infoRow + 1), 'تاريخ الإنشاء: ' . date('Y-m-d H:i:s'));

                if (!empty($this->filters['start_date'])) {
                    $sheet->setCellValue('A' . ($infoRow + 2), 'تاريخ البدء: ' . $this->filters['start_date']);
                }

                if (!empty($this->filters['end_date'])) {
                    $sheet->setCellValue('A' . ($infoRow + 3), 'تاريخ النهاية: ' . $this->filters['end_date']);
                }
            }
        ];
    }

    private function createRevenueChart($sheet, $lastRow, $summaryRow)
    {
        // إعداد بيانات المخطط
        $dataSeriesLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Worksheet!$C$1', null, 1),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Worksheet!$D$1', null, 1),
        ];

        $xAxisTickValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Worksheet!$A$2:$A$' . $lastRow, null, $lastRow - 1),
        ];

        $dataSeriesValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'Worksheet!$C$2:$C$' . $lastRow, null, $lastRow - 1),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'Worksheet!$D$2:$D$' . $lastRow, null, $lastRow - 1),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_STACKED,
            range(0, count($dataSeriesValues) - 1),
            $dataSeriesLabels,
            $xAxisTickValues,
            $dataSeriesValues
        );

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);

        $title = new ChartTitle('توزيع الإيرادات الشهري');
        $chart = new Chart(
            'revenue_chart',
            $title,
            $legend,
            $plotArea,
            true,
            0,
            null,
            null
        );

        $chart->setTopLeftPosition('H' . ($summaryRow + 2));
        $chart->setBottomRightPosition('P' . ($summaryRow + 20));

        $sheet->addChart($chart);
    }

    public function title(): string
    {
        return 'تقرير الإيرادات';
    }
}
