<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Constants\Constants;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats()
    {
        try {
            $totalClients = Client::active()->count();
            $totalInvoices = Invoice::active()->count();
            $paidInvoices = Invoice::active()->paid()->count();
            $revenue = (float) Invoice::active()->paid()->sum('total');

            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            $thisMonthInvoices = Invoice::active()
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            $newClientsThisMonth = Client::active()
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            $averageInvoice = $totalInvoices > 0 ? $revenue / $totalInvoices : 0;
            $paymentRate = $totalInvoices > 0 ? ($paidInvoices / $totalInvoices) * 100 : 0;

            return response()->json([
                'status' => true,
                'data' => [
                    'totalClients' => $totalClients,
                    'totalInvoices' => $totalInvoices,
                    'paidInvoices' => $paidInvoices,
                    'revenue' => $revenue,
                    'thisMonthInvoices' => $thisMonthInvoices,
                    'newClientsThisMonth' => $newClientsThisMonth,
                    'averageInvoice' => round($averageInvoice, 2),
                    'collectionRate' => round($paymentRate, 2),
                    'clientsGrowth' => 0,
                    'invoiceGrowth' => 0,
                    'revenueGrowth' => 0,
                    'paymentRate' => round($paymentRate, 2)
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard stats error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'خطأ في جلب الإحصائيات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function monthlyRevenue()
    {
        try {
            $revenue = [];

            for ($i = 5; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $startOfMonth = $month->copy()->startOfMonth();
                $endOfMonth = $month->copy()->endOfMonth();

                $monthRevenue = Invoice::active()
                    ->paid()
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->sum('total');

                $revenue[] = [
                    'month' => $month->translatedFormat('M'),
                    'revenue' => (float) $monthRevenue
                ];
            }

            return response()->json([
                'status' => true,
                'data' => $revenue
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard monthly revenue error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'خطأ في جلب الإيرادات الشهرية',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function overdueInvoices()
    {
        try {
            $overdueInvoices = Invoice::active()
                ->where(function($query) {
                    $query->where('status', Constants::INVOICE_STATUS_OVERDUE)
                          ->orWhere(function($q) {
                              $q->where('status', Constants::INVOICE_STATUS_SENT)
                                ->where('due_date', '<', Carbon::now());
                          });
                })
                ->with(['client' => function($query) {
                    $query->select('id', 'name');
                }])
                ->orderBy('due_date', 'asc')
                ->take(10)
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'client_name' => $invoice->client ? $invoice->client->name : 'غير معروف',
                        'due_date' => $invoice->due_date,
                        'total' => (float) $invoice->total,
                        'status' => $invoice->status,
                        'days_overdue' => Carbon::parse($invoice->due_date)->diffInDays(Carbon::now())
                    ];
                });

            return response()->json([
                'status' => true,
                'data' => $overdueInvoices
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard overdue invoices error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'خطأ في جلب الفواتير المتأخرة'
            ], 500);
        }
    }

    public function recentActivity()
    {
        try {
            // يمكنك إضافة نموذج ActivityLog لاحقاً
            $recentActivity = [];

            return response()->json([
                'status' => true,
                'data' => $recentActivity
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard recent activity error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'خطأ في جلب النشاط الأخير'
            ], 500);
        }
    }

    public function recentInvoices()
    {
        try {
            $recentInvoices = Invoice::active()
                ->with(['client' => function($query) {
                    $query->select('id', 'name');
                }])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'client_name' => $invoice->client ? $invoice->client->name : 'غير معروف',
                        'total' => (float) $invoice->total,
                        'status' => $invoice->status,
                        'due_date' => $invoice->due_date,
                        'created_at' => $invoice->created_at
                    ];
                });

            return response()->json([
                'status' => true,
                'data' => $recentInvoices
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard recent invoices error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'خطأ في جلب الفواتير الحديثة'
            ], 500);
        }
    }

    public function topClients()
    {
        try {
            $topClients = Client::active()
                ->withCount([
                    'invoices' => function($query) {
                        $query->where('status', Constants::INVOICE_STATUS_PAID);
                    }
                ])
                ->withSum([
                    'invoices' => function($query) {
                        $query->where('status', Constants::INVOICE_STATUS_PAID);
                    }
                ], 'total')
                ->orderBy('invoices_sum_total', 'desc')
                ->take(5)
                ->get()
                ->map(function ($client) {
                    return [
                        'id' => $client->id,
                        'name' => $client->name,
                        'email' => $client->email,
                        'phone' => $client->phone,
                        'company_name' => $client->company_name,
                        'paid_invoices_count' => (int) $client->invoices_count,
                        'total_spent' => (float) $client->invoices_sum_total,
                        'created_at' => $client->created_at
                    ];
                });

            return response()->json([
                'status' => true,
                'data' => $topClients
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard top clients error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'خطأ في جلب العملاء'
            ], 500);
        }
    }

    // دالة تجلب جميع بيانات الداشبورد في استجابة واحدة
    public function dashboard()
    {
        try {
            $stats = $this->stats()->getData();
            $recentInvoices = $this->recentInvoices()->getData();
            $topClients = $this->topClients()->getData();
            $monthlyRevenue = $this->monthlyRevenue()->getData();
            $overdueInvoices = $this->overdueInvoices()->getData();
            $recentActivity = $this->recentActivity()->getData();

            return response()->json([
                'status' => true,
                'data' => [
                    'stats' => $stats->status ? $stats->data : [],
                    'recentInvoices' => $recentInvoices->status ? $recentInvoices->data : [],
                    'recentClients' => $topClients->status ? $topClients->data : [],
                    'monthlyRevenue' => $monthlyRevenue->status ? $monthlyRevenue->data : [],
                    'overdueInvoices' => $overdueInvoices->status ? $overdueInvoices->data : [],
                    'recentActivity' => $recentActivity->status ? $recentActivity->data : []
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard all data error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'خطأ في جلب بيانات الداشبورد'
            ], 500);
        }
    }
}
