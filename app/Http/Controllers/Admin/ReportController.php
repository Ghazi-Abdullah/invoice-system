<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repository\Admin\Report\ReportInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Report\ReportFilterRequest;
use Illuminate\Support\Facades\Storage;

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
    public function invoices(ReportFilterRequest $request): JsonResponse
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
    public function clients(ReportFilterRequest $request): JsonResponse
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
    public function revenue(ReportFilterRequest $request): JsonResponse
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
    public function overdue(ReportFilterRequest $request): JsonResponse
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
     * تصدير التقرير
     */
    public function export(Request $request, string $type): JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
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

            // إذا طلب تحميل مباشر
            if ($request->has('download') && $request->input('download') === '1') {
                return $this->downloadReport($type, $filters);
            }

            // حفظ في الخادم
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
     * تحميل التقرير مباشرة
     */
    private function downloadReport(string $type, array $filters): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $export = $this->reportRepository->exportReport($type, $filters);
        $filePath = storage_path('app/public/' . $export['file_path']);

        return response()->download($filePath, $export['file_name']);
    }

    /**
     * الحصول على الملفات المصدرة
     */
    public function exportedFiles(): JsonResponse
    {
        try {
            $exportsPath = storage_path('app/public/exports');
            $files = [];

            if (file_exists($exportsPath)) {
                $fileList = scandir($exportsPath);

                foreach ($fileList as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $filePath = $exportsPath . '/' . $file;
                        $files[] = [
                            'name' => $file,
                            'size' => $this->formatSize(filesize($filePath)),
                            'modified' => date('Y-m-d H:i:s', filemtime($filePath)),
                            'url' => asset('storage/exports/' . $file)
                        ];
                    }
                }
            }

            // ترتيب حسب تاريخ التعديل (الأحدث أولاً)
            usort($files, function($a, $b) {
                return strtotime($b['modified']) - strtotime($a['modified']);
            });

            return response()->json([
                'success' => true,
                'message' => 'تم جلب الملفات المصدرة بنجاح',
                'data' => $files
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب الملفات: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * حذف ملف مصدر
     */
    public function deleteExportedFile(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file_name' => 'required|string'
            ]);

            $fileName = $request->input('file_name');
            $filePath = storage_path('app/public/exports/' . $fileName);

            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'الملف غير موجود'
                ], 404);
            }

            if (unlink($filePath)) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف الملف بنجاح'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في حذف الملف'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في حذف الملف: ' . $e->getMessage()
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

    /**
     * تنسيق حجم الملف
     */
    private function formatSize($bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes == 1) {
            return $bytes . ' byte';
        } else {
            return '0 bytes';
        }
    }
}
