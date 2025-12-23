<?php
// app/Repository/Admin/Report/ReportRepository.php
namespace App\Repository\Admin\Report;

use App\Models\Invoice;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportRepository implements ReportInterface
{
    public function invoiceReport($filters = [])
    {
        $user = Auth::user();
        if (!$user->hasPermission('view_reports')) {
            throw new \Exception('ليس لديك صلاحية لعرض التقارير');
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
            'total_paid' => $invoices->where('status', 'paid')->sum('total_amount'),
            'total_due' => $invoices->whereIn('status', ['sent', 'overdue'])->sum('total_amount'),
        ];

        return [
            'invoices' => $invoices,
            'stats' => $stats
        ];
    }

    public function clientReport($filters = [])
    {
        $user = Auth::user();
        if (!$user->hasPermission('view_reports')) {
            throw new \Exception('ليس لديك صلاحية لعرض التقارير');
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
        if (!$user->hasPermission('view_reports')) {
            throw new \Exception('ليس لديك صلاحية لعرض التقارير');
        }

        // الإيرادات الشهرية
        $monthlyRevenue = Invoice::select(
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
        if (!$user->hasPermission('view_reports')) {
            throw new \Exception('ليس لديك صلاحية لعرض التقارير');
        }

        $overdueInvoices = Invoice::whereIn('status', ['sent', 'overdue'])
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
        if (!$user->hasPermission('export_reports')) {
            throw new \Exception('ليس لديك صلاحية لتصدير التقارير');
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
                throw new \Exception('نوع التقرير غير صالح');
        }
    }
}
