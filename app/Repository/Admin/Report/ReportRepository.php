<?php

namespace App\Repository\Admin\Report;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\Payment;
use App\Models\User;
use App\Constants\Constants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportRepository implements ReportInterface
{
    /**
     * تقرير الفواتير
     */
    public function getInvoiceReport(array $filters = []): array
    {
        $query = Invoice::with(['client', 'items'])
            ->when(!empty($filters['start_date']), function ($q) use ($filters) {
                $q->whereDate('invoice_date', '>=', $filters['start_date']);
            })
            ->when(!empty($filters['end_date']), function ($q) use ($filters) {
                $q->whereDate('invoice_date', '<=', $filters['end_date']);
            })
            ->when(!empty($filters['status']), function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->when(!empty($filters['client_id']), function ($q) use ($filters) {
                $q->where('client_id', $filters['client_id']);
            })
            ->orderBy('created_at', 'desc');

        // الحصول على النتائج
        $perPage = $filters['per_page'] ?? 20;
        $invoices = $query->paginate($perPage);

        // حساب الإحصائيات
        $stats = [
            'total_invoices' => $invoices->total(),
            'total_amount' => $invoices->sum('total'),
            'total_paid' => $invoices->where('status', Constants::INVOICE_STATUS_PAID)->sum('total'),
            'total_due' => $invoices->whereIn('status', [Constants::INVOICE_STATUS_SENT, Constants::INVOICE_STATUS_OVERDUE])->sum('total'),
        ];

        return [
            'data' => $invoices->items(),
            'stats' => $stats,
            'pagination' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
                'from' => $invoices->firstItem(),
                'to' => $invoices->lastItem()
            ]
        ];
    }

    /**
     * تقرير العملاء
     */
    public function getClientReport(array $filters = []): array
    {
        $query = Client::withCount(['invoices' => function ($q) use ($filters) {
                if (!empty($filters['start_date'])) {
                    $q->whereDate('created_at', '>=', $filters['start_date']);
                }
                if (!empty($filters['end_date'])) {
                    $q->whereDate('created_at', '<=', $filters['end_date']);
                }
            }])
            ->withSum(['invoices' => function ($q) use ($filters) {
                if (!empty($filters['start_date'])) {
                    $q->whereDate('created_at', '>=', $filters['start_date']);
                }
                if (!empty($filters['end_date'])) {
                    $q->whereDate('created_at', '<=', $filters['end_date']);
                }
            }], 'total')
            ->orderBy('invoices_count', 'desc');

        if (!empty($filters['client_id'])) {
            $query->where('id', $filters['client_id']);
        }

        $clients = $query->get();

        $stats = [
            'total_clients' => $clients->count(),
            'active_clients' => $clients->where('invoices_count', '>', 0)->count(),
            'total_invoices' => $clients->sum('invoices_count'),
            'total_revenue' => $clients->sum('invoices_sum_total')
        ];

        return [
            'data' => $clients->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'company_name' => $client->company_name,
                    'invoices_count' => $client->invoices_count,
                    'total_spent' => $client->invoices_sum_total,
                    'average_invoice' => $client->invoices_count > 0
                        ? round($client->invoices_sum_total / $client->invoices_count, 2)
                        : 0
                ];
            }),
            'stats' => $stats
        ];
    }

    /**
     * تقرير الإيرادات
     */
    public function getRevenueReport(array $filters = []): array
    {
        $query = Invoice::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(total) as total_amount'),
                DB::raw('SUM(CASE WHEN status = "paid" THEN total ELSE 0 END) as paid_amount')
            )
            ->when(!empty($filters['start_date']), function ($q) use ($filters) {
                $q->whereDate('created_at', '>=', $filters['start_date']);
            })
            ->when(!empty($filters['end_date']), function ($q) use ($filters) {
                $q->whereDate('created_at', '<=', $filters['end_date']);
            })
            ->when(!empty($filters['status']), function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->groupBy('month')
            ->orderBy('month', 'desc');

        $revenueData = $query->get();

        $stats = [
            'total_revenue' => $revenueData->sum('total_amount'),
            'collected_revenue' => $revenueData->sum('paid_amount'),
            'outstanding_revenue' => $revenueData->sum('total_amount') - $revenueData->sum('paid_amount'),
            'collection_rate' => $revenueData->sum('total_amount') > 0
                ? round(($revenueData->sum('paid_amount') / $revenueData->sum('total_amount')) * 100, 2)
                : 0
        ];

        return [
            'data' => $revenueData->map(function ($item) {
                return [
                    'month' => $item->month,
                    'invoice_count' => $item->invoice_count,
                    'total_amount' => $item->total_amount,
                    'paid_amount' => $item->paid_amount,
                    'due_amount' => $item->total_amount - $item->paid_amount
                ];
            }),
            'stats' => $stats
        ];
    }

    /**
     * تقرير المتأخرات
     */
    public function getOverdueReport(array $filters = []): array
    {
        $query = Invoice::with(['client'])
            ->where(function ($q) {
                $q->where('status', Constants::INVOICE_STATUS_OVERDUE)
                  ->orWhere(function ($sub) {
                      $sub->where('status', Constants::INVOICE_STATUS_SENT)
                          ->whereDate('due_date', '<', now());
                  });
            })
            ->when(!empty($filters['start_date']), function ($q) use ($filters) {
                $q->whereDate('created_at', '>=', $filters['start_date']);
            })
            ->when(!empty($filters['end_date']), function ($q) use ($filters) {
                $q->whereDate('created_at', '<=', $filters['end_date']);
            })
            ->orderBy('due_date', 'asc');

        $overdueInvoices = $query->get();

        $stats = [
            'total_overdue' => $overdueInvoices->count(),
            'total_amount' => $overdueInvoices->sum('total'),
            'average_days_overdue' => round($overdueInvoices->avg(function ($invoice) {
                return max(0, now()->diffInDays($invoice->due_date));
            }))
        ];

        return [
            'data' => $overdueInvoices->map(function ($invoice) {
                $daysOverdue = max(0, now()->diffInDays($invoice->due_date));

                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'client' => [
                        'id' => $invoice->client_id,
                        'name' => $invoice->client->name ?? 'غير محدد'
                    ],
                    'due_date' => $invoice->due_date,
                    'total_amount' => $invoice->total,
                    'paid_amount' => $invoice->paid_amount ?? 0,
                    'due_amount' => $invoice->total - ($invoice->paid_amount ?? 0),
                    'status' => $invoice->status,
                    'days_overdue' => $daysOverdue
                ];
            }),
            'stats' => $stats
        ];
    }

    /**
     * إحصائيات لوحة التحكم
     */
    public function getDashboardStats(): array
    {
        $user = auth()->user();

        return [
            'total_invoices' => Invoice::count(),
            'total_amount' => Invoice::sum('total'),
            'paid_invoices' => Invoice::where('status', Constants::INVOICE_STATUS_PAID)->count(),
            'paid_amount' => Invoice::where('status', Constants::INVOICE_STATUS_PAID)->sum('total'),
            'overdue_invoices' => Invoice::where('status', Constants::INVOICE_STATUS_OVERDUE)->count(),
            'overdue_amount' => Invoice::where('status', Constants::INVOICE_STATUS_OVERDUE)->sum('total'),
            'total_clients' => Client::count()
        ];
    }

    /**
     * تصدير التقرير
     */
    public function exportReport(string $type, array $filters = []): array
    {
        // هنا يمكنك إضافة كود التصدير إلى Excel
        // هذا مثال بسيط

        $report = match($type) {
            'invoices' => $this->getInvoiceReport($filters),
            'clients' => $this->getClientReport($filters),
            'revenue' => $this->getRevenueReport($filters),
            'overdue' => $this->getOverdueReport($filters),
            default => throw new \InvalidArgumentException("نوع التقرير غير صالح: {$type}")
        };

        $fileName = "report_{$type}_" . date('Y_m_d_His') . '.json';
        $filePath = storage_path('app/public/exports/' . $fileName);

        // إنشاء المجلد إذا لم يكن موجوداً
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        // حفظ البيانات في ملف (مؤقتاً)
        file_put_contents($filePath, json_encode($report, JSON_PRETTY_PRINT));

        return [
            'file_path' => 'storage/exports/' . $fileName,
            'file_name' => $fileName
        ];
    }

    /**
     * إرسال تذكير
     */
    public function sendInvoiceReminder(int $invoiceId): array
    {
        $invoice = Invoice::with('client')->findOrFail($invoiceId);

        // هنا يتم إرسال البريد الإلكتروني
        // Mail::to($invoice->client->email)->send(new InvoiceReminder($invoice));

        return [
            'message' => 'تم إرسال التذكير بنجاح',
            'invoice' => $invoice
        ];
    }

    /**
     * تسديد فاتورة
     */
    public function markInvoiceAsPaid(int $invoiceId): array
    {
        DB::beginTransaction();

        try {
            $invoice = Invoice::findOrFail($invoiceId);

            if ($invoice->status === Constants::INVOICE_STATUS_PAID) {
                throw new \Exception('الفاتورة مدفوعة بالفعل');
            }

            $invoice->update([
                'status' => Constants::INVOICE_STATUS_PAID,
                'paid_at' => now(),
                'payment_date' => now()->format('Y-m-d')
            ]);

            // إنشاء سجل الدفع
            Payment::create([
                'invoice_id' => $invoiceId,
                'amount' => $invoice->total,
                'payment_date' => now(),
                'payment_method' => 'manual',
                'notes' => 'تم التسديد من خلال التقارير'
            ]);

            DB::commit();

            return [
                'message' => 'تم تسديد الفاتورة بنجاح',
                'invoice' => $invoice->load('client')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
