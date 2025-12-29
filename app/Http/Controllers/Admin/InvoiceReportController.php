<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use App\Models\Invoice;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceReportController extends Controller
{
    use ResponseTrait;

    public function index(Request $request)
    {
        try {
            // التحقق من الصلاحية
            if (!PermissionHelper::checkPermission(Constants::VIEW_REPORTS)) {
                return $this->failureResponse(
                    __('messages.no_permission'),
                    null,
                    Constants::RESPONSE_FORBIDDEN
                );
            }

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

            $perPage = $request->per_page ?? Constants::DEFAULT_PER_PAGE;
            $perPage = min($perPage, Constants::MAX_PER_PAGE);

            $invoices = $query->orderBy('issue_date', 'desc')->paginate($perPage);

            $stats = [
                'total_invoices' => $invoices->total(),
                'total_amount' => $invoices->sum('total_amount'),
                'total_paid' => $invoices->where('status', Constants::INVOICE_STATUS_PAID)->sum('total_amount'),
                'total_due' => $invoices->whereIn('status', [Constants::INVOICE_STATUS_SENT, Constants::INVOICE_STATUS_OVERDUE])->sum('total_amount'),
            ];

            return $this->successResponse(
                __('messages.invoice_report_fetched'),
                [
                    'invoices' => $invoices,
                    'stats' => $stats
                ]
            );

        } catch (\Exception $e) {
            Log::error('Error fetching invoice report: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'request' => $request->all()
            ]);
            return $this->failureResponse(
                __('messages.error') . ': ' . $e->getMessage(),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function clients(Request $request)
    {
        try {
            // التحقق من الصلاحية
            if (!PermissionHelper::checkPermission(Constants::VIEW_REPORTS)) {
                return $this->failureResponse(
                    __('messages.no_permission'),
                    null,
                    Constants::RESPONSE_FORBIDDEN
                );
            }

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

            return $this->successResponse(
                __('messages.client_report_fetched'),
                [
                    'clients' => $clients,
                    'stats' => $stats
                ]
            );

        } catch (\Exception $e) {
            Log::error('Error fetching client report: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null
            ]);
            return $this->failureResponse(
                __('messages.error') . ': ' . $e->getMessage(),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function revenue(Request $request)
    {
        try {
            // التحقق من الصلاحية
            if (!PermissionHelper::checkPermission(Constants::VIEW_REPORTS)) {
                return $this->failureResponse(
                    __('messages.no_permission'),
                    null,
                    Constants::RESPONSE_FORBIDDEN
                );
            }

            $userId = $request->user()->id;

            // الإيرادات الشهرية
            $monthlyRevenue = Invoice::where('user_id', $userId)
                ->select(
                    DB::raw('DATE_FORMAT(issue_date, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as invoice_count'),
                    DB::raw('SUM(total_amount) as total_amount'),
                    DB::raw('SUM(CASE WHEN status = "' . Constants::INVOICE_STATUS_PAID . '" THEN total_amount ELSE 0 END) as paid_amount')
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

            return $this->successResponse(
                __('messages.monthly_revenue_fetched'),
                [
                    'monthly_revenue' => $monthlyRevenue,
                    'status_revenue' => $statusRevenue,
                    'stats' => $stats
                ]
            );

        } catch (\Exception $e) {
            Log::error('Error fetching revenue report: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null
            ]);
            return $this->failureResponse(
                __('messages.error') . ': ' . $e->getMessage(),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function overdue(Request $request)
    {
        try {
            // التحقق من الصلاحية
            if (!PermissionHelper::checkPermission(Constants::VIEW_REPORTS)) {
                return $this->failureResponse(
                    __('messages.no_permission'),
                    null,
                    Constants::RESPONSE_FORBIDDEN
                );
            }

            $userId = $request->user()->id;

            $overdueInvoices = Invoice::where('user_id', $userId)
                ->whereIn('status', [Constants::INVOICE_STATUS_SENT, Constants::INVOICE_STATUS_OVERDUE])
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

            return $this->successResponse(
                __('messages.overdue_invoices_fetched'),
                [
                    'invoices' => $overdueInvoices,
                    'stats' => $stats
                ]
            );

        } catch (\Exception $e) {
            Log::error('Error fetching overdue report: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null
            ]);
            return $this->failureResponse(
                __('messages.error') . ': ' . $e->getMessage(),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }
}
