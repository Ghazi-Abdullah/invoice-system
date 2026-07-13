<?php
namespace App\Repository\Admin\Dashboard;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardRepository implements DashboardInterface
{
    public function getDashboardData(User $user): array
    {
        try {
            $today = Carbon::today();
            $currentMonth = Carbon::now()->startOfMonth();
            $lastMonth = Carbon::now()->subMonth()->startOfMonth();
            $sixMonthsAgo = Carbon::now()->subMonths(6)->startOfMonth();

            // إحصائيات كاملة
            $stats = $this->getCompleteStats($today, $currentMonth, $lastMonth);

            // البيانات الحديثة
            $recentClients = $this->getRecentClients(5);
            $recentInvoices = $this->getRecentInvoices(5);
            $monthlyRevenue = $this->getMonthlyRevenue($sixMonthsAgo);
            $overdueInvoices = $this->getOverdueInvoices();
            $recentActivity = $this->getRecentActivity(10);
            $topClients = $this->getTopClients(5);

            // توزيع حالة الفواتير
            $invoiceStatuses = [
                [
                    'status' => 'paid',
                    'label' => 'مدفوعة',
                    'value' => $stats['paidInvoices'],
                    'color' => '#10b981',
                    'icon' => 'fas fa-check-circle',
                    'amount' => $stats['paidAmount'],
                    'percentage' => $this->calculatePercentage($stats['paidInvoices'], $stats['totalInvoices'])
                ],
                [
                    'status' => 'sent',
                    'label' => 'مرسلة',
                    'value' => $stats['pendingInvoices'],
                    'color' => '#f59e0b',
                    'icon' => 'fas fa-clock',
                    'amount' => $stats['pendingAmount'],
                    'percentage' => $this->calculatePercentage($stats['pendingInvoices'], $stats['totalInvoices'])
                ],
                [
                    'status' => 'overdue',
                    'label' => 'متأخرة',
                    'value' => $stats['overdueInvoices'],
                    'color' => '#ef4444',
                    'icon' => 'fas fa-exclamation-triangle',
                    'amount' => $stats['overdueAmount'],
                    'percentage' => $this->calculatePercentage($stats['overdueInvoices'], $stats['totalInvoices'])
                ],
                [
                    'status' => 'draft',
                    'label' => 'مسودة',
                    'value' => $stats['draftInvoices'],
                    'color' => '#6b7280',
                    'icon' => 'fas fa-file-alt',
                    'amount' => $stats['draftAmount'],
                    'percentage' => $this->calculatePercentage($stats['draftInvoices'], $stats['totalInvoices'])
                ]
            ];

            // بيانات الأداء الشهري للرسم البياني
            $performanceData = $this->getPerformanceData($sixMonthsAgo);

            return [
                'stats' => $stats,
                'recentClients' => $recentClients,
                'recentInvoices' => $recentInvoices,
                'monthlyRevenue' => $monthlyRevenue,
                'overdueInvoices' => $overdueInvoices,
                'recentActivity' => $recentActivity,
                'topClients' => $topClients,
                'invoiceStatuses' => $invoiceStatuses,
                'performanceData' => $performanceData,
                'summary' => [
                    'performance_today' => $this->calculateTodayPerformance($stats),
                    'chart_periods' => [
                        ['label' => '1M', 'value' => '1m'],
                        ['label' => '3M', 'value' => '3m'],
                        ['label' => '6M', 'value' => '6m'],
                        ['label' => '1Y', 'value' => '1y'],
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Dashboard data aggregation failed: ' . $e->getMessage());

            return $this->getFallbackData();
        }
    }

    private function getCompleteStats($today, $currentMonth, $lastMonth)
    {
        // إجمالي الإيرادات
        $totalRevenue = (float) Invoice::where('status', 'paid')->sum('total');

        // إيرادات هذا الشهر
        $currentMonthRevenue = (float) Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$currentMonth, Carbon::now()])
            ->sum('total');

        // إيرادات الشهر الماضي
        $lastMonthRevenue = (float) Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$lastMonth, $currentMonth])
            ->sum('total');

        // نمو الإيرادات
        $revenueGrowth = $lastMonthRevenue > 0 ?
            (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 :
            ($currentMonthRevenue > 0 ? 100 : 0);

        // إجمالي الفواتير
        $totalInvoices = Invoice::count();

        // فواتير هذا الشهر
        $currentMonthInvoices = Invoice::whereBetween('created_at', [$currentMonth, Carbon::now()])->count();

        // فواتير الشهر الماضي
        $lastMonthInvoices = Invoice::whereBetween('created_at', [$lastMonth, $currentMonth])->count();

        // نمو الفواتير
        $invoiceGrowth = $lastMonthInvoices > 0 ?
            (($currentMonthInvoices - $lastMonthInvoices) / $lastMonthInvoices) * 100 :
            ($currentMonthInvoices > 0 ? 100 : 0);

        // إجمالي العملاء
        $totalClients = Client::count();

        // عملاء هذا الشهر
        $currentMonthClients = Client::whereBetween('created_at', [$currentMonth, Carbon::now()])->count();

        // عملاء الشهر الماضي
        $lastMonthClients = Client::whereBetween('created_at', [$lastMonth, $currentMonth])->count();

        // نمو العملاء
        $clientsGrowth = $lastMonthClients > 0 ?
            (($currentMonthClients - $lastMonthClients) / $lastMonthClients) * 100 :
            ($currentMonthClients > 0 ? 100 : 0);

        // الفواتير حسب الحالة
        $paidInvoices = Invoice::where('status', 'paid')->count();
        $pendingInvoices = Invoice::where('status', 'sent')->count();
        $overdueInvoices = Invoice::where('status', 'overdue')->count();
        $draftInvoices = Invoice::where('status', 'draft')->count();

        // المبالغ حسب الحالة
        $paidAmount = (float) Invoice::where('status', 'paid')->sum('total');
        $pendingAmount = (float) Invoice::where('status', 'sent')->sum('total');
        $overdueAmount = (float) Invoice::where('status', 'overdue')->sum('total');
        $draftAmount = (float) Invoice::where('status', 'draft')->sum('total');

        // معدل الدفع
        $paymentRate = $totalInvoices > 0 ? ($paidInvoices / $totalInvoices) * 100 : 0;

        // متوسط الإيرادات الشهرية
        $avgResult = Invoice::where('status', 'paid')
            ->select(DB::raw('AVG(total) as avg_revenue'))
            ->first();
        $avgMonthlyRevenue = $avgResult ? (float) $avgResult->avg_revenue : 0;

        // إحصائيات اليوم
        $todayPaidInvoices = Invoice::where('status', 'paid')
            ->whereDate('paid_at', $today)
            ->count();

        $todayTotalInvoices = Invoice::whereDate('created_at', $today)->count();

        return [
            'totalRevenue' => $totalRevenue,
            'totalInvoices' => $totalInvoices,
            'totalClients' => $totalClients,
            'paidInvoices' => $paidInvoices,
            'pendingInvoices' => $pendingInvoices,
            'overdueInvoices' => $overdueInvoices,
            'draftInvoices' => $draftInvoices,
            'revenueGrowth' => round($revenueGrowth, 2),
            'invoiceGrowth' => round($invoiceGrowth, 2),
            'clientsGrowth' => round($clientsGrowth, 2),
            'paymentRate' => round($paymentRate, 2),
            'avgMonthlyRevenue' => round($avgMonthlyRevenue, 2),
            'paidAmount' => $paidAmount,
            'pendingAmount' => $pendingAmount,
            'overdueAmount' => $overdueAmount,
            'draftAmount' => $draftAmount,
            'todayPaidInvoices' => $todayPaidInvoices,
            'todayTotalInvoices' => $todayTotalInvoices,
            'currentMonthRevenue' => $currentMonthRevenue,
            'currentMonthInvoices' => $currentMonthInvoices,
            'currentMonthClients' => $currentMonthClients,
        ];
    }

    private function getRecentClients($limit = 5)
    {
        $clientGrowth = $this->getClientGrowthRates();

        return Client::latest()
            ->take($limit)
            ->get()
            ->map(function ($client) use ($clientGrowth) {
                // حساب إجمالي الإنفاق
                $totalSpent = Invoice::where('client_id', $client->id)
                    ->where('status', 'paid')
                    ->sum('total');

                // حساب إجمالي الفواتير
                $totalInvoices = Invoice::where('client_id', $client->id)->count();

                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'company_name' => $client->company_name ?: 'غير محدد',
                    'status' => $client->status ?: 'active',
                    'total_spent' => (float) $totalSpent,
                    'total_invoices' => $totalInvoices,
                    'growth' => $clientGrowth[$client->id] ?? 0.0,
                    'created_at' => $client->created_at->format('Y-m-d H:i:s'),
                ];
            })->toArray();
    }

    /**
     * نمو حقيقي لكل عميل: مقارنة إجمالي مدفوعاته هذا الشهر بالشهر الماضي.
     * محسوبة بـ 2 استعلام تجميعي فقط (بدل استعلام لكل عميل)، ومشتركة بين
     * getRecentClients() و getTopClients() لتفادي التكرار.
     *
     * @return array<int, float> [client_id => نسبة النمو]
     */
    private function getClientGrowthRates(): array
    {
        $currentMonthStart = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();

        $currentMonthByClient = Invoice::where('status', 'paid')
            ->where('paid_at', '>=', $currentMonthStart)
            ->select('client_id', DB::raw('SUM(total) as total'))
            ->groupBy('client_id')
            ->pluck('total', 'client_id');

        $lastMonthByClient = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$lastMonthStart, $currentMonthStart])
            ->select('client_id', DB::raw('SUM(total) as total'))
            ->groupBy('client_id')
            ->pluck('total', 'client_id');

        $growthRates = [];
        $clientIds = $currentMonthByClient->keys()->merge($lastMonthByClient->keys())->unique();

        foreach ($clientIds as $clientId) {
            $currentMonthSpent = (float) ($currentMonthByClient[$clientId] ?? 0);
            $lastMonthSpent = (float) ($lastMonthByClient[$clientId] ?? 0);

            $growthRates[$clientId] = $lastMonthSpent > 0
                ? round((($currentMonthSpent - $lastMonthSpent) / $lastMonthSpent) * 100, 2)
                : ($currentMonthSpent > 0 ? 100.0 : 0.0);
        }

        return $growthRates;
    }

    private function getRecentInvoices($limit = 5)
    {
        return Invoice::with('client')
            ->latest()
            ->take($limit)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'client_name' => $invoice->client ? $invoice->client->name : 'غير محدد',
                    'client_company' => $invoice->client ? $invoice->client->company_name : 'غير محدد',
                    'total' => (float) $invoice->total,
                    'status' => $invoice->status,
                    'issue_date' => $invoice->issue_date,
                    'due_date' => $invoice->due_date,
                    'paid_at' => $invoice->paid_at,
                    'created_at' => $invoice->created_at->format('Y-m-d H:i:s'),
                ];
            })->toArray();
    }

    private function getMonthlyRevenue($startDate)
    {
        $revenues = Invoice::where('status', 'paid')
            ->where('paid_at', '>=', $startDate)
            ->select(
                DB::raw('DATE_FORMAT(paid_at, "%Y-%m") as month'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as invoice_count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // أسماء الأشهر العربية
        $monthNames = [
            '01' => 'يناير', '02' => 'فبراير', '03' => 'مارس',
            '04' => 'أبريل', '05' => 'مايو', '06' => 'يونيو',
            '07' => 'يوليو', '08' => 'أغسطس', '09' => 'سبتمبر',
            '10' => 'أكتوبر', '11' => 'نوفمبر', '12' => 'ديسمبر'
        ];

        return $revenues->map(function ($item) use ($monthNames) {
            [$year, $month] = explode('-', $item->month);
            return [
                'month' => ($monthNames[$month] ?? $month) . ' ' . $year,
                'revenue' => (float) $item->revenue,
                'invoice_count' => (int) $item->invoice_count,
                'year' => $year,
                'month_number' => $month
            ];
        })->toArray();
    }

    private function getOverdueInvoices()
    {
        return Invoice::where('status', 'overdue')
            ->with('client')
            ->latest()
            ->get()
            ->map(function ($invoice) {
                $daysOverdue = $invoice->due_date ?
                    Carbon::parse($invoice->due_date)->diffInDays(Carbon::now()) : 0;

                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'client_name' => $invoice->client ? $invoice->client->name : 'غير محدد',
                    'total' => (float) $invoice->total,
                    'due_date' => $invoice->due_date,
                    'days_overdue' => $daysOverdue,
                    'created_at' => $invoice->created_at->format('Y-m-d H:i:s'),
                ];
            })->toArray();
    }

    private function getRecentActivity($limit = 10)
    {
        // نحاول جلب النشاط من سجلات الفواتير
        $recentInvoices = Invoice::with('client')
            ->latest()
            ->take($limit)
            ->get();

        $activities = [];
        $counter = 1;

        foreach ($recentInvoices as $invoice) {
            $activities[] = [
                'id' => $counter++,
                'type' => 'invoice_created',
                'title' => 'فاتورة جديدة',
                'description' => 'فاتورة #' . $invoice->invoice_number . ' تم إنشاؤها للعميل ' . ($invoice->client->name ?? 'غير محدد'),
                'amount' => (float) $invoice->total,
                'user_name' => 'النظام',
                'invoiceId' => $invoice->invoice_number,
                'timestamp' => $invoice->created_at->timestamp,
                'created_at' => $invoice->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // إضافة نشاطات للفواتير المدفوعة
        $paidInvoices = Invoice::where('status', 'paid')
            ->latest()
            ->take(3)
            ->get();

        foreach ($paidInvoices as $invoice) {
            $activities[] = [
                'id' => $counter++,
                'type' => 'invoice_paid',
                'title' => 'فاتورة مدفوعة',
                'description' => 'فاتورة #' . $invoice->invoice_number . ' تم دفعها',
                'amount' => (float) $invoice->total,
                'user_name' => 'النظام',
                'invoiceId' => $invoice->invoice_number,
                'timestamp' => $invoice->paid_at ? Carbon::parse($invoice->paid_at)->timestamp : Carbon::now()->timestamp,
                'created_at' => $invoice->paid_at ?: $invoice->updated_at->format('Y-m-d H:i:s'),
            ];
        }

        // إضافة نشاطات للعملاء الجدد
        $recentClients = Client::latest()->take(2)->get();
        foreach ($recentClients as $client) {
            $activities[] = [
                'id' => $counter++,
                'type' => 'client_added',
                'title' => 'عميل جديد',
                'description' => 'تم إضافة العميل ' . $client->name,
                'user_name' => 'النظام',
                'clientId' => $client->id,
                'timestamp' => $client->created_at->timestamp,
                'created_at' => $client->created_at->format('Y-m-d H:i:s'),
            ];
        } 

        // ترتيب حسب التاريخ الأحدث
        usort($activities, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return array_slice($activities, 0, $limit);
    }

    private function getTopClients($limit = 5)
    {
        // إجمالي المدفوع لكل عميل على مر الزمن
        $totalSpentByClient = Invoice::where('status', 'paid')
            ->select('client_id', DB::raw('SUM(total) as total'))
            ->groupBy('client_id')
            ->pluck('total', 'client_id');

        $clientGrowth = $this->getClientGrowthRates();

        return Client::whereIn('id', $totalSpentByClient->keys())
            ->get()
            ->map(function ($client) use ($totalSpentByClient, $clientGrowth) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'company_name' => $client->company_name ?: 'غير محدد',
                    'total_spent' => (float) ($totalSpentByClient[$client->id] ?? 0),
                    'growth' => $clientGrowth[$client->id] ?? 0.0,
                    'created_at' => $client->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->sortByDesc('total_spent')
            ->take($limit)
            ->values()
            ->toArray();
    }

    private function getPerformanceData($startDate)
    {
        $data = Invoice::where('paid_at', '>=', $startDate)
            ->select(
                DB::raw('DATE_FORMAT(paid_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // أسماء الأشهر العربية
        $monthNames = [
            '01' => 'يناير', '02' => 'فبراير', '03' => 'مارس',
            '04' => 'أبريل', '05' => 'مايو', '06' => 'يونيو',
            '07' => 'يوليو', '08' => 'أغسطس', '09' => 'سبتمبر',
            '10' => 'أكتوبر', '11' => 'نوفمبر', '12' => 'ديسمبر'
        ];

        $result = [
            'months' => [],
            'invoices' => [],
            'revenues' => []
        ];

        foreach ($data as $item) {
            [$year, $month] = explode('-', $item->month);
            $result['months'][] = ($monthNames[$month] ?? $month) . ' ' . $year;
            $result['invoices'][] = (int) $item->invoice_count;
            $result['revenues'][] = (float) $item->revenue;
        }

        // إذا لم يكن هناك بيانات فعلية، نعرض آخر 6 أشهر بقيمة صفر حقيقية
        // (بدل بيانات عشوائية كانت تُظهر إيرادات وهمية لمنشأة جديدة بلا مبيعات بعد)
        if (empty($result['months'])) {
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $result['months'][] = ($monthNames[$date->format('m')] ?? $date->format('m')) . ' ' . $date->format('Y');
                $result['invoices'][] = 0;
                $result['revenues'][] = 0;
            }
        }

        return $result;
    }

    private function calculatePercentage($part, $total)
    {
        return $total > 0 ? round(($part / $total) * 100, 2) : 0;
    }

    private function calculateTodayPerformance($stats)
    {
        $todayPaid = $stats['todayPaidInvoices'] ?? 0;
        $todayTotal = $stats['todayTotalInvoices'] ?? 0;
        return $todayTotal > 0 ? round(($todayPaid / $todayTotal) * 100, 2) : 0;
    }

    private function getFallbackData()
    {
        $monthNames = [
            '01' => 'يناير', '02' => 'فبراير', '03' => 'مارس',
            '04' => 'أبريل', '05' => 'مايو', '06' => 'يونيو',
            '07' => 'يوليو', '08' => 'أغسطس', '09' => 'سبتمبر',
            '10' => 'أكتوبر', '11' => 'نوفمبر', '12' => 'ديسمبر'
        ];

        $performanceData = [
            'months' => [],
            'invoices' => [],
            'revenues' => []
        ];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $performanceData['months'][] = ($monthNames[$date->format('m')] ?? $date->format('m')) . ' ' . $date->format('Y');
            // صفر حقيقي، مو أرقام وهمية قد يُخطئ أحد ويظنها بيانات فعلية
            $performanceData['invoices'][] = 0;
            $performanceData['revenues'][] = 0;
        }

        return [
            'stats' => [
                'totalRevenue' => 0,
                'totalInvoices' => 0,
                'totalClients' => 0,
                'paidInvoices' => 0,
                'pendingInvoices' => 0,
                'overdueInvoices' => 0,
                'draftInvoices' => 0,
                'revenueGrowth' => 0,
                'invoiceGrowth' => 0,
                'clientsGrowth' => 0,
                'paymentRate' => 0,
                'avgMonthlyRevenue' => 0,
                'paidAmount' => 0,
                'pendingAmount' => 0,
                'overdueAmount' => 0,
                'draftAmount' => 0,
                'todayPaidInvoices' => 0,
                'todayTotalInvoices' => 0,
                'currentMonthRevenue' => 0,
                'currentMonthInvoices' => 0,
                'currentMonthClients' => 0
            ],
            'recentClients' => [],
            'recentInvoices' => [],
            'monthlyRevenue' => [],
            'overdueInvoices' => [],
            'recentActivity' => [],
            'topClients' => [],
            'invoiceStatuses' => [
                [
                    'status' => 'paid',
                    'label' => 'مدفوعة',
                    'value' => 0,
                    'color' => '#10b981',
                    'icon' => 'fas fa-check-circle',
                    'amount' => 0,
                    'percentage' => 0
                ],
                [
                    'status' => 'sent',
                    'label' => 'مرسلة',
                    'value' => 0,
                    'color' => '#f59e0b',
                    'icon' => 'fas fa-clock',
                    'amount' => 0,
                    'percentage' => 0
                ],
                [
                    'status' => 'overdue',
                    'label' => 'متأخرة',
                    'value' => 0,
                    'color' => '#ef4444',
                    'icon' => 'fas fa-exclamation-triangle',
                    'amount' => 0,
                    'percentage' => 0
                ],
                [
                    'status' => 'draft',
                    'label' => 'مسودة',
                    'value' => 0,
                    'color' => '#6b7280',
                    'icon' => 'fas fa-file-alt',
                    'amount' => 0,
                    'percentage' => 0
                ]
            ],
            'performanceData' => $performanceData,
            'summary' => [
                'performance_today' => 0,
                'chart_periods' => [
                    ['label' => '1M', 'value' => '1m'],
                    ['label' => '3M', 'value' => '3m'],
                    ['label' => '6M', 'value' => '6m'],
                    ['label' => '1Y', 'value' => '1y'],
                ]
            ],
            // يوضّح للواجهة أن هذه بيانات احتياطية (فشل حقيقي في جلب البيانات) وليست بيانات حقيقية من قاعدة البيانات
            'is_fallback' => true,
        ];
    }
}
