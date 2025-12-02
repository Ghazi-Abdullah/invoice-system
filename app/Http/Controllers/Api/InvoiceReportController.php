<?php
// app/Http/Controllers/Api/InvoiceReportController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Admin\InvoiceSummary\InvoiceSummaryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceReportController extends Controller
{
    protected $invoiceSummaryRepository;

    public function __construct(InvoiceSummaryInterface $invoiceSummaryRepository)
    {
        $this->invoiceSummaryRepository = $invoiceSummaryRepository;
    }

    public function index(Request $request)
    {
        try {
            Log::info('📊 جلب تقرير الفواتير', [
                'user_id' => auth()->id(),
                'filters' => $request->all()
            ]);

            $data = $this->invoiceSummaryRepository->index($request);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'تم جلب تقرير الفواتير بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في جلب تقرير الفواتير: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب تقرير الفواتير: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clientReport(Request $request)
    {
        try {
            $userId = auth()->id();
            $data = $this->invoiceSummaryRepository->getClientReport($request, $userId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'تم جلب تقرير العملاء بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في جلب تقرير العملاء: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب تقرير العملاء: ' . $e->getMessage()
            ], 500);
        }
    }

    public function overdueReport(Request $request)
    {
        try {
            $userId = auth()->id();
            $data = $this->invoiceSummaryRepository->getOverdueReport($request, $userId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'تم جلب تقرير الفواتير المتأخرة بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في جلب تقرير الفواتير المتأخرة: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب تقرير الفواتير المتأخرة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function revenueReport(Request $request)
    {
        try {
            $userId = auth()->id();
            $data = $this->invoiceSummaryRepository->getRevenueReport($request, $userId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'تم جلب تقرير الإيرادات بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في جلب تقرير الإيرادات: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب تقرير الإيرادات: ' . $e->getMessage()
            ], 500);
        }
    }
}
