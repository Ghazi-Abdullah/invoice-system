<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Admin\Report\ReportInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Report\ReportFilterRequest;

class ReportController extends Controller
{
    protected $reportRepository;

    public function __construct(ReportInterface $reportRepository)
    {
        $this->reportRepository = $reportRepository;
    }

    /**
     * تقرير الفواتير
     */
    public function getInvoiceReport(ReportFilterRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $report = $this->reportRepository->getInvoiceReport($filters);

            return response()->json([
                'success' => true,
                'message' => 'تم تحميل تقرير الفواتير بنجاح',
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تحميل التقرير: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تقرير العملاء
     */
    public function getClientReport(ReportFilterRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $report = $this->reportRepository->getClientReport($filters);

            return response()->json([
                'success' => true,
                'message' => 'تم تحميل تقرير العملاء بنجاح',
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تحميل التقرير: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تقرير الإيرادات
     */
    public function getRevenueReport(ReportFilterRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $report = $this->reportRepository->getRevenueReport($filters);

            return response()->json([
                'success' => true,
                'message' => 'تم تحميل تقرير الإيرادات بنجاح',
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تحميل التقرير: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تقرير المتأخرات
     */
    public function getOverdueReport(ReportFilterRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $report = $this->reportRepository->getOverdueReport($filters);

            return response()->json([
                'success' => true,
                'message' => 'تم تحميل تقرير المتأخرات بنجاح',
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تحميل التقرير: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إحصائيات لوحة التحكم
     */
    public function getDashboardStats(): JsonResponse
    {
        try {
            $stats = $this->reportRepository->getDashboardStats();

            return response()->json([
                'success' => true,
                'message' => 'تم تحميل الإحصائيات بنجاح',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تحميل الإحصائيات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تصدير التقرير
     */
    public function exportReport(Request $request, string $type): JsonResponse
    {
        try {
            $filters = $request->all();

            // إضافة تواريخ افتراضية إذا لم تكن موجودة
            if (empty($filters['start_date'])) {
                $filters['start_date'] = now()->subDays(30)->format('Y-m-d');
            }
            if (empty($filters['end_date'])) {
                $filters['end_date'] = now()->format('Y-m-d');
            }

            $export = $this->reportRepository->exportReport($type, $filters);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء ملف التصدير بنجاح',
                'data' => $export
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تصدير التقرير: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إرسال تذكير
     */
    public function sendReminder(Request $request, int $invoiceId): JsonResponse
    {
        try {
            $result = $this->reportRepository->sendInvoiceReminder($invoiceId);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['invoice']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في إرسال التذكير: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تسديد فاتورة
     */
    public function markAsPaid(Request $request, int $invoiceId): JsonResponse
    {
        try {
            $result = $this->reportRepository->markInvoiceAsPaid($invoiceId);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['invoice']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تسديد الفاتورة: ' . $e->getMessage()
            ], 500);
        }
    }
}
