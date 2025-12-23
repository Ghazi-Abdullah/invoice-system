<?php
// app/Repository/User/Report/ReportRepository.php
namespace App\Repository\User\Report;

use App\Models\Invoice;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportRepository implements ReportInterface
{
    public function invoiceReport($filters = [])
    {
        $user = Auth::user();

        $query = Invoice::where('user_id', $user->id)
            ->with(['client']);

        if (isset($filters['start_date']) && $filters['start_date']) {
            $query->whereDate('issue_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date']) && $filters['end_date']) {
            $query->whereDate('issue_date', '<=', $filters['end_date']);
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        $invoices = $query->orderBy('issue_date', 'desc')->paginate(20);

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

    public function clientReport()
    {
        $user = Auth::user();

        $clients = Client::where('user_id', $user->id)
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

        return [
            'clients' => $clients,
            'stats' => $stats
        ];
    }

    public function getDashboardStats()
    {
        $user = Auth::user();

        $totalClients = Client::where('user_id', $user->id)->count();
        $totalInvoices = Invoice::where('user_id', $user->id)->count();
        $totalAmount = Invoice::where('user_id', $user->id)->sum('total_amount');
        $paidInvoices = Invoice::where('user_id', $user->id)->where('status', 'paid')->count();
        $paidAmount = Invoice::where('user_id', $user->id)->where('status', 'paid')->sum('total_amount');
        $overdueInvoices = Invoice::where('user_id', $user->id)->where('status', 'overdue')->count();

        // هذا الشهر
        $startOfMonth = now()->startOfMonth();
        $thisMonthInvoices = Invoice::where('user_id', $user->id)
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        // الشهر الماضي
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();
        $lastMonthInvoices = Invoice::where('user_id', $user->id)
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        // حساب النمو
        $clientsGrowth = $totalClients > 0 ? 0 : 100; // إذا لم يكن هناك عملاء من قبل، يعتبر نمو 100%
        if ($lastMonthInvoices > 0) {
            $invoiceGrowth = (($thisMonthInvoices - $lastMonthInvoices) / $lastMonthInvoices) * 100;
        } else {
            $invoiceGrowth = $thisMonthInvoices > 0 ? 100 : 0;
        }

        return [
            'total_clients' => $totalClients,
            'total_invoices' => $totalInvoices,
            'total_amount' => $totalAmount,
            'paid_invoices' => $paidInvoices,
            'paid_amount' => $paidAmount,
            'overdue_invoices' => $overdueInvoices,
            'this_month_invoices' => $thisMonthInvoices,
            'clients_growth' => round($clientsGrowth, 2),
            'invoice_growth' => round($invoiceGrowth, 2),
            'average_invoice' => $totalInvoices > 0 ? round($totalAmount / $totalInvoices, 2) : 0,
            'payment_rate' => $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 2) : 0,
        ];
    }
}
