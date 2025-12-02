<?php
// app/Repositories/Admin/InvoiceSummary/InvoiceSummaryRepository.php
namespace App\Repositories\Admin\InvoiceSummary;

use App\Models\Invoice;
use App\Models\Client;
use App\Repositories\Admin\InvoiceSummary\InvoiceSummaryInterface;
use App\Repositories\BaseRepository;
use Carbon\Carbon;

class InvoiceSummaryRepository extends BaseRepository implements InvoiceSummaryInterface
{
    protected $model;

    public function __construct()
    {
        $this->model = new Invoice();
    }

    public function index($request)
    {
        $userId = auth()->id();
        $data = [];

        $query = Invoice::with(['client', 'items'])
            ->where('user_id', $userId);

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('issue_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('issue_date', '<=', $request->end_date);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('client_id') && $request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $invoices = $query->get();

        // الإحصائيات الأساسية
        $data['stats'] = [
            'total_invoices' => $invoices->count(),
            'total_amount' => $invoices->sum('total_amount'),
            'total_paid' => $invoices->where('status', 'paid')->sum('total_amount'),
            'total_due' => $invoices->where('status', '!=', 'paid')->sum('total_amount'),
            'total_clients' => $invoices->unique('client_id')->count(),
            'overdue_invoices' => $invoices->where('status', '!=', 'paid')
                ->whereDate('due_date', '<', now())->count()
        ];

        // الفواتير حسب الحالة
        $data['invoices_by_status'] = $this->getInvoicesByStatus($invoices);

        // الفواتير الشهرية
        $data['invoices_by_month'] = $this->getInvoicesByMonth($invoices);

        // بيانات للجدول
        $data['invoices'] = $query->paginate($request->per_page ?? 15);

        // العملاء للفلاتر
        $data['clients'] = Client::where('user_id', $userId)->get(['id', 'name', 'email']);

        return $data;
    }

    public function getClientReport($request, $userId)
    {
        $invoices = Invoice::with('client')
            ->where('user_id', $userId)
            ->when($request->has('start_date'), function ($q) use ($request) {
                $q->whereDate('issue_date', '>=', $request->start_date);
            })
            ->when($request->has('end_date'), function ($q) use ($request) {
                $q->whereDate('issue_date', '<=', $request->end_date);
            })
            ->get();

        $clients = $invoices->groupBy('client_id')->map(function ($clientInvoices) {
            $client = $clientInvoices->first()->client;

            return [
                'client_id' => $client->id,
                'client_name' => $client->name,
                'total_invoices' => $clientInvoices->count(),
                'total_amount' => $clientInvoices->sum('total_amount'),
                'total_paid' => $clientInvoices->where('status', 'paid')->sum('total_amount'),
                'total_due' => $clientInvoices->where('status', '!=', 'paid')->sum('total_amount')
            ];
        })->values();

        return [
            'clients' => $clients,
            'summary' => [
                'total_clients' => $clients->count(),
                'total_invoices' => $invoices->count(),
                'total_revenue' => $invoices->sum('total_amount')
            ]
        ];
    }

    public function getOverdueReport($request, $userId)
    {
        $overdueInvoices = Invoice::with(['client', 'items'])
            ->where('user_id', $userId)
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<', now())
            ->when($request->has('start_date'), function ($q) use ($request) {
                $q->whereDate('due_date', '>=', $request->start_date);
            })
            ->when($request->has('end_date'), function ($q) use ($request) {
                $q->whereDate('due_date', '<=', $request->end_date);
            })
            ->get()
            ->map(function ($invoice) {
                $daysOverdue = now()->diffInDays(Carbon::parse($invoice->due_date));

                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'client_name' => $invoice->client->name,
                    'due_date' => $invoice->due_date,
                    'days_overdue' => $daysOverdue,
                    'total_amount' => $invoice->total_amount
                ];
            });

        return [
            'overdue_invoices' => $overdueInvoices,
            'summary' => [
                'total_overdue' => $overdueInvoices->count(),
                'total_amount' => $overdueInvoices->sum('total_amount')
            ]
        ];
    }

    public function getRevenueReport($request, $userId)
    {
        $invoices = Invoice::where('user_id', $userId)
            ->when($request->has('start_date'), function ($q) use ($request) {
                $q->whereDate('issue_date', '>=', $request->start_date);
            })
            ->when($request->has('end_date'), function ($q) use ($request) {
                $q->whereDate('issue_date', '<=', $request->end_date);
            })
            ->get();

        $revenueData = $invoices->groupBy(function ($invoice) {
            return Carbon::parse($invoice->issue_date)->format('Y-m');
        })->map(function ($group, $month) {
            $date = Carbon::createFromFormat('Y-m', $month);

            return [
                'month' => $date->format('F Y'),
                'count' => $group->count(),
                'total' => $group->sum('total_amount'),
                'paid' => $group->where('status', 'paid')->sum('total_amount'),
                'due' => $group->where('status', '!=', 'paid')->sum('total_amount')
            ];
        })->sortBy(function ($item, $key) {
            return $key;
        })->values();

        return [
            'revenue_data' => $revenueData,
            'summary' => [
                'total_revenue' => $invoices->sum('total_amount'),
                'collected_revenue' => $invoices->where('status', 'paid')->sum('total_amount'),
                'outstanding_revenue' => $invoices->where('status', '!=', 'paid')->sum('total_amount')
            ]
        ];
    }

    // ===== Methods Private =====

    private function getInvoicesByStatus($invoices)
    {
        $statuses = ['draft', 'sent', 'paid', 'overdue'];
        $result = [];

        foreach ($statuses as $status) {
            $count = $invoices->where('status', $status)->count();
            $amount = $invoices->where('status', $status)->sum('total_amount');

            $result[] = [
                'status' => $status,
                'count' => $count,
                'amount' => $amount,
                'percentage' => $invoices->count() > 0 ? ($count / $invoices->count()) * 100 : 0
            ];
        }

        return $result;
    }

    private function getInvoicesByMonth($invoices)
    {
        $grouped = $invoices->groupBy(function ($invoice) {
            return Carbon::parse($invoice->issue_date)->format('Y-m');
        });

        return $grouped->map(function ($group, $month) {
            $date = Carbon::createFromFormat('Y-m', $month);

            return [
                'month' => $date->format('F Y'),
                'count' => $group->count(),
                'total' => $group->sum('total_amount'),
                'paid' => $group->where('status', 'paid')->sum('total_amount'),
                'due' => $group->where('status', '!=', 'paid')->sum('total_amount')
            ];
        })->sortBy(function ($item, $key) {
            return $key;
        })->values();
    }
}
