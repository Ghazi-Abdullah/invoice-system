<?php

namespace App\Services;

use App\Repository\Admin\Report\ReportInterface;
use App\Exports\Reports\InvoiceReportExport;
use App\Exports\Reports\ClientReportExport;
use App\Exports\Reports\RevenueReportExport;
use App\Exports\Reports\OverdueReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ReportService
{
    protected $reportRepository;

    public function __construct(ReportInterface $reportRepository)
    {
        $this->reportRepository = $reportRepository;
    }

    /**
     * Generate report based on type
     */
    public function generateReport(string $type, array $filters = []): array
    {
        $method = 'get' . ucfirst($type) . 'Report';

        if (!method_exists($this->reportRepository, $method)) {
            throw new \InvalidArgumentException("Report type {$type} not supported");
        }

        return $this->reportRepository->$method($filters);
    }

    /**
     * Export report to Excel
     */
    public function exportReport(string $type, array $filters): array
    {
        $report = $this->generateReport($type, $filters);

        $exportClass = match($type) {
            'invoices' => InvoiceReportExport::class,
            'clients' => ClientReportExport::class,
            'revenue' => RevenueReportExport::class,
            'overdue' => OverdueReportExport::class,
            default => throw new \InvalidArgumentException("Export type {$type} not supported")
        };

        $filename = "report_{$type}_" . Carbon::now()->format('Y_m_d_H_i_s') . '.xlsx';
        $path = 'exports/reports/' . $filename;

        Excel::store(new $exportClass($report), $path, 'public');

        return [
            'url' => asset('storage/' . $path),
            'filename' => $filename
        ];
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        return [
            'overview' => $this->reportRepository->getOverviewStats(),
            'recent_activity' => $this->reportRepository->getRecentActivity(),
            'top_clients' => $this->reportRepository->getTopClients(),
            'monthly_revenue' => $this->reportRepository->getMonthlyRevenue()
        ];
    }
}
