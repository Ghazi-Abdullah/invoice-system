<?php
namespace App\Repository\Admin\Report;

use App\Models\Invoice;
use App\Models\Client;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportRepository implements ReportInterface
{
    public function invoiceReport($filters = [])
    {
        $user = Auth::user();
        if (!PermissionHelper::checkPermission(Constants::VIEW_REPORTS)) {
            throw new \Exception(__('messages.no_permission'));
        }

        $query = Invoice::with(['client', 'user']);

        if (isset($filters['start_date']) && $filters['start_date']) {
            $query->whereDate('issue_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date']) && $filters['end_date']) {
            $query->whereDate('issue_date', '<=', $filters['end_date']);
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id']) && $filters['user_id']) {
            $query->where('user_id', $filters['user_id']);
        }

        $invoices = $query->orderBy('issue_date', 'desc')->paginate(50);

        $stats = [
            'total_invoices' => $invoices->total(),
            'total_amount' => $invoices->sum('total_amount'),
            'total_paid' => $invoices->where('status', Constants::INVOICE_STATUS_PAID)->sum('total_amount'),
            'total_due' => $invoices->whereIn('status', [Constants::INVOICE_STATUS_SENT, Constants::INVOICE_STATUS_OVERDUE])->sum('total_amount'),
        ];

        return [
            'invoices' => $invoices,
            'stats' => $stats
        ];
    }

    public function clientReport($filters = [])
    {
        $user = Auth::user();
        if (!PermissionHelper::checkPermission(Constants::VIEW_REPORTS)) {
            throw new \Exception(__('messages.no_permission'));
        }

        $clients = Client::withCount(['invoices'])
            ->withSum('invoices', 'total_amount')
            ->orderBy('invoices_sum_total_amount', 'desc')
            ->get();

        $stats = [
            'total_clients' => $clients->count(),
            'total_invoices' => $clients->sum('invoices_count'),
            'total_revenue' => $clients->sum('invoices_sum_total_amount'),
            'active_clients' => $clients->where('invoices_count', '>', 0)->count(),
        ];

        return [
            'clients' => $clients,
            'stats' => $stats
        ];
    }

    public function revenueReport($filters = [])
    {
        $user = Auth::user();
        if (!PermissionHelper::checkPermission(Constants::VIEW_REPORTS)) {
            throw new \Exception(__('messages.no_permission'));
        }

        // الإيرادات الشهرية
        $monthlyRevenue = Invoice::select(
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
        $statusRevenue = Invoice::select(
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

        return [
            'monthly_revenue' => $monthlyRevenue,
            'status_revenue' => $statusRevenue,
            'stats' => $stats
        ];
    }

    public function overdueReport($filters = [])
    {
        $user = Auth::user();
        if (!PermissionHelper::checkPermission(Constants::VIEW_REPORTS)) {
            throw new \Exception(__('messages.no_permission'));
        }

        $overdueInvoices = Invoice::whereIn('status', [Constants::INVOICE_STATUS_SENT, Constants::INVOICE_STATUS_OVERDUE])
            ->where('due_date', '<', now())
            ->with(['client', 'user'])
            ->orderBy('due_date')
            ->get();

        $stats = [
            'total_overdue' => $overdueInvoices->count(),
            'total_amount' => $overdueInvoices->sum('total_amount'),
            'average_days_overdue' => $overdueInvoices->avg(
                DB::raw('DATEDIFF(NOW(), due_date)')
            ),
        ];

        return [
            'invoices' => $overdueInvoices,
            'stats' => $stats
        ];
    }

    public function exportReport($type, $filters = [])
    {
        $user = Auth::user();
        if (!PermissionHelper::checkPermission(Constants::EXPORT_REPORTS)) {
            throw new \Exception(__('messages.no_permission'));
        }

        switch ($type) {
            case 'invoices':
                return $this->invoiceReport($filters);
            case 'clients':
                return $this->clientReport($filters);
            case 'revenue':
                return $this->revenueReport($filters);
            case 'overdue':
                return $this->overdueReport($filters);
            default:
                throw new \Exception(__('messages.invalid_report_type'));
        }
    }

    public function recentActivity($request)
    {
        try {
            if (!PermissionHelper::checkPermission(Constants::VIEW_REPORTS)) {
                throw new \Exception(__('messages.no_permission'));
            }

            $limit = $request->limit ?? 10;
            $activities = \App\Models\ActivityLog::with(['user'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return [
                'status' => true,
                'message' => __('messages.recent_activity_fetched'),
                'data' => $activities
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => __('messages.error') . ': ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function topClients($request)
    {
        try {
            if (!PermissionHelper::checkPermission(Constants::VIEW_REPORTS)) {
                throw new \Exception(__('messages.no_permission'));
            }

            $limit = $request->limit ?? 10;
            $clients = Client::withCount(['invoices'])
                ->withSum('invoices', 'total_amount')
                ->orderBy('invoices_sum_total_amount', 'desc')
                ->limit($limit)
                ->get();

            return [
                'status' => true,
                'message' => __('messages.top_clients_fetched'),
                'data' => $clients
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => __('messages.error') . ': ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function monthlyRevenue($request)
    {
        try {
            if (!PermissionHelper::checkPermission(Constants::VIEW_REPORTS)) {
                throw new \Exception(__('messages.no_permission'));
            }

            $months = $request->months ?? 12;
            $revenue = Invoice::select(
                    DB::raw('DATE_FORMAT(issue_date, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as invoice_count'),
                    DB::raw('SUM(total_amount) as total_amount'),
                    DB::raw('SUM(CASE WHEN status = "' . Constants::INVOICE_STATUS_PAID . '" THEN total_amount ELSE 0 END) as paid_amount')
                )
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit($months)
                ->get();

            return [
                'status' => true,
                'message' => __('messages.monthly_revenue_fetched'),
                'data' => $revenue
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => __('messages.error') . ': ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
