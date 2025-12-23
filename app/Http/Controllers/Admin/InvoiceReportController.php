<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceReportController extends Controller
{
    public function __construct()
    {
        // تعليق مؤقت للصلاحيات حتى يتم تعيين الأدوار
        // $this->middleware('permission:view_reports')->only(['index', 'clients', 'revenue', 'overdue']);
    }

    public function index(Request $request)
    {
        try {
            $userId = $request->user()->id;

            $query = Invoice::where('user_id', $userId)
                ->with(['client']);

            // تطبيق الفلاتر
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('issue_date', [
                    $request->start_date,
                    $request->end_date
                ]);
            }

            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            $invoices = $query->orderBy('issue_date', 'desc')->paginate(50);

            $stats = [
                'total_invoices' => $invoices->total(),
                'total_amount' => $invoices->sum('total_amount'),
                'total_paid' => $invoices->where('status', 'paid')->sum('total_amount'),
                'total_due' => $invoices->whereIn('status', ['sent', 'overdue'])->sum('total_amount'),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'invoices' => $invoices,
                    'stats' => $stats
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في جلب تقرير الفواتير: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب التقرير'
            ], 500);
        }
    }

    public function clients(Request $request)
    {
        try {
            $userId = $request->user()->id;

            $clients = Client::where('user_id', $userId)
                ->withCount(['invoices'])
                ->withSum('invoices', 'total_amount')
                ->orderBy('invoices_sum_total_amount', 'desc')
                ->get();

            $stats = [
                'total_clients' => $clients->count(),
                'total_invoices' => $clients->sum('invoices_count'),
                'total_revenue' => $clients->sum('invoices_sum_total_amount'),
                'active_clients' => $clients->where('invoices_count', '>', 0)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'clients' => $clients,
                    'stats' => $stats
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في جلب تقرير العملاء: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب التقرير'
            ], 500);
        }
    }

    public function revenue(Request $request)
    {
        try {
            $userId = $request->user()->id;

            // الإيرادات الشهرية
            $monthlyRevenue = Invoice::where('user_id', $userId)
                ->select(
                    DB::raw('DATE_FORMAT(issue_date, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as invoice_count'),
                    DB::raw('SUM(total_amount) as total_amount'),
                    DB::raw('SUM(CASE WHEN status = "paid" THEN total_amount ELSE 0 END) as paid_amount')
                )
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get();

            // الإيرادات حسب الحالة
            $statusRevenue = Invoice::where('user_id', $userId)
                ->select(
                    'status',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(total_amount) as amount')
                )
                ->groupBy('status')
                ->get();

            $stats = [
                'total_revenue' => $monthlyRevenue->sum('total_amount'),
                'total_paid' => $monthlyRevenue->sum('paid_amount'),
                'average_invoice' => $monthlyRevenue->avg('total_amount'),
                'months' => $monthlyRevenue->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'monthly_revenue' => $monthlyRevenue,
                    'status_revenue' => $statusRevenue,
                    'stats' => $stats
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في جلب تقرير الإيرادات: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب التقرير'
            ], 500);
        }
    }

    public function overdue(Request $request)
    {
        try {
            $userId = $request->user()->id;

            $overdueInvoices = Invoice::where('user_id', $userId)
                ->whereIn('status', ['sent', 'overdue'])
                ->where('due_date', '<', now())
                ->with(['client'])
                ->orderBy('due_date')
                ->get();

            $stats = [
                'total_overdue' => $overdueInvoices->count(),
                'total_amount' => $overdueInvoices->sum('total_amount'),
                'average_days_overdue' => $overdueInvoices->avg(
                    DB::raw('DATEDIFF(NOW(), due_date)')
                ),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'invoices' => $overdueInvoices,
                    'stats' => $stats
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في جلب تقرير الفواتير المتأخرة: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب التقرير'
            ], 500);
        }
    }
}
