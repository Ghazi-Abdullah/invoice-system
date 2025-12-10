<?php

namespace App\Repositories\Admin\InvoiceSummary;

use App\Models\Invoice;
use App\Models\Client;
use App\Repositories\Admin\InvoiceSummary\InvoiceSummaryInterface;
use App\Repositories\BaseRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceSummaryRepository extends BaseRepository implements InvoiceSummaryInterface
{
    public function __construct()
    {
        parent::__construct(new Invoice());
    }

    public function index($request)
    {
        $userId = auth()->id();
        $data = [];

        // 1. بناء الاستعلام الأساسي
        $query = Invoice::with(['client', 'items'])
            ->where('user_id', $userId);

        // تطبيق الفلاتر على الاستعلام الرئيسي
        $this->applyFilters($query, $request);

        // 2. جلب البيانات مع pagination
        $data['invoices'] = $query->paginate($request->per_page ?? 15);

        // 3. جلب جميع الفواتير المفلترة للإحصائيات
        $filteredInvoices = Invoice::where('user_id', $userId);
        $this->applyFilters($filteredInvoices, $request);
        $allInvoices = $filteredInvoices->get();

        // 4. معالجة الإحصائيات
        $data['stats'] = $this->processStats($allInvoices);

        // 5. الفواتير حسب الحالة
        $data['invoices_by_status'] = $this->getInvoicesByStatus($allInvoices);

        // 6. الفواتير الشهرية
        $data['invoices_by_month'] = $this->getInvoicesByMonth($allInvoices);

        // 7. إجماليات
        $data['total_invoices'] = $allInvoices->count();
        $data['total_amount'] = $allInvoices->sum('total_amount');
        $data['total_paid'] = $allInvoices->where('status', 'paid')->sum('total_amount');
        $data['total_due'] = $allInvoices->where('status', '!=', 'paid')->sum('total_amount');
        $data['total_clients'] = $allInvoices->unique('client_id')->count();

        // 8. العملاء للفلاتر - استخدام دالة جديدة من الـ controller
        $data['clients'] = Client::where('user_id', $userId)->get(['id', 'name', 'email']);

        // 9. بيانات الفترة
        $data['start_date'] = $request->start_date ?? null;
        $data['end_date'] = $request->end_date ?? null;

        return $data;
    }

    public function getClientReport($request)
    {
        $userId = auth()->id();

        $query = Invoice::with('client')
            ->where('user_id', $userId);

        // تطبيق الفلاتر على الاستعلام
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('issue_date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('issue_date', '<=', $request->end_date);
        }

        $invoices = $query->get();

        $clients = $invoices->groupBy('client_id')->map(function ($clientInvoices) {
            $client = $clientInvoices->first()->client;

            return [
                'client_id' => $client->id ?? null,
                'client_name' => $client->name ?? 'غير معروف',
                'client_email' => $client->email ?? 'غير معروف',
                'total_invoices' => $clientInvoices->count(),
                'total_amount' => $clientInvoices->sum('total_amount'),
                'total_paid' => $clientInvoices->where('status', 'paid')->sum('total_amount'),
                'total_due' => $clientInvoices->where('status', '!=', 'paid')->sum('total_amount'),
                'average_invoice_amount' => $clientInvoices->avg('total_amount')
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

    public function getOverdueReport($request)
    {
        $userId = auth()->id();

        $query = Invoice::with(['client', 'items'])
            ->where('user_id', $userId)
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<', now());

        // تطبيق الفلاتر الإضافية
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('due_date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('due_date', '<=', $request->end_date);
        }

        $overdueInvoices = $query->get()->map(function ($invoice) {
            $daysOverdue = now()->diffInDays(Carbon::parse($invoice->due_date));

            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'client_name' => $invoice->client->name ?? 'غير معروف',
                'issue_date' => $invoice->issue_date,
                'due_date' => $invoice->due_date,
                'days_overdue' => $daysOverdue,
                'total_amount' => $invoice->total_amount,
                'status' => $invoice->status,
                'contact_info' => [
                    'email' => $invoice->client->email ?? 'غير معروف',
                    'phone' => $invoice->client->phone ?? 'غير معروف'
                ]
            ];
        });

        return [
            'overdue_invoices' => $overdueInvoices,
            'summary' => [
                'total_overdue' => $overdueInvoices->count(),
                'total_amount' => $overdueInvoices->sum('total_amount'),
                'average_days_overdue' => $overdueInvoices->avg('days_overdue') ?? 0
            ]
        ];
    }

    public function getRevenueReport($request)
    {
        $userId = auth()->id();

        $query = Invoice::where('user_id', $userId);

        // تطبيق الفلاتر على الاستعلام
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('issue_date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('issue_date', '<=', $request->end_date);
        }

        $invoices = $query->get();

        // تجميع حسب الشهر
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
                'outstanding_revenue' => $invoices->where('status', '!=', 'paid')->sum('total_amount'),
                'collection_rate' => $invoices->sum('total_amount') > 0 ?
                    ($invoices->where('status', 'paid')->sum('total_amount') / $invoices->sum('total_amount')) * 100 : 0
            ]
        ];
    }

    // ===== Methods Private =====

    /**
     * تطبيق الفلاتر على استعلام
     */
    private function applyFilters($query, $request)
    {
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

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('invoice_number', 'like', '%' . $request->search . '%')
                    ->orWhereHas('client', function ($clientQuery) use ($request) {
                        $clientQuery->where('name', 'like', '%' . $request->search . '%')
                            ->orWhere('email', 'like', '%' . $request->search . '%');
                    });
            });
        }

        return $query;
    }

    /**
     * معالجة الإحصائيات
     */
    private function processStats($invoices)
    {
        $totalInvoices = $invoices->count();
        $totalAmount = $invoices->sum('total_amount');

        // تصفية الفواتير المتأخرة باستخدام filter بدلاً من whereDate
        $overdueInvoices = $invoices->filter(function ($invoice) {
            return $invoice->status != 'paid'
                && $invoice->due_date
                && Carbon::parse($invoice->due_date)->lt(now());
        })->count();

        return [
            'total_invoices' => $totalInvoices,
            'total_amount' => $totalAmount,
            'total_due' => $invoices->where('status', '!=', 'paid')->sum('total_amount'),
            'total_paid' => $invoices->where('status', 'paid')->sum('total_amount'),
            'total_clients' => $invoices->unique('client_id')->count(),
            'overdue_invoices' => $overdueInvoices,
            'average_invoice_amount' => $totalInvoices > 0 ? $totalAmount / $totalInvoices : 0
        ];
    }

    /**
     * الفواتير حسب الحالة
     */
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

    /**
     * الفواتير حسب الشهر
     */
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
