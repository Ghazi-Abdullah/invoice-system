<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\User;
use App\Constants\Constants;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    protected $cacheTime = 300; // 5 دقائق

    public function getDashboardStats($userId = null, $isAdmin = false)
    {
        $cacheKey = 'dashboard_stats_' . ($userId ?? 'all') . '_' . ($isAdmin ? 'admin' : 'user');

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($userId, $isAdmin) {
            $query = $this->getBaseQuery($userId, $isAdmin);

            return [
                'total_invoices' => (clone $query)->count(),
                'total_amount' => (clone $query)->sum('total_amount'),
                'paid_invoices' => (clone $query)->where('status', Constants::INVOICE_STATUS_PAID)->count(),
                'paid_amount' => (clone $query)->where('status', Constants::INVOICE_STATUS_PAID)->sum('total_amount'),
                'sent_invoices' => (clone $query)->where('status', Constants::INVOICE_STATUS_SENT)->count(),
                'sent_amount' => (clone $query)->where('status', Constants::INVOICE_STATUS_SENT)->sum('total_amount'),
                'overdue_invoices' => (clone $query)->where('status', Constants::INVOICE_STATUS_OVERDUE)->count(),
                'overdue_amount' => (clone $query)->where('status', Constants::INVOICE_STATUS_OVERDUE)->sum('total_amount'),
                'draft_invoices' => (clone $query)->where('status', Constants::INVOICE_STATUS_DRAFT)->count(),
            ];
        });
    }

    public function getMonthlyRevenue($userId = null, $isAdmin = false, $months = 12)
    {
        $cacheKey = 'monthly_revenue_' . ($userId ?? 'all') . '_' . ($isAdmin ? 'admin' : 'user') . '_' . $months;

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($userId, $isAdmin, $months) {
            $query = $this->getBaseQuery($userId, $isAdmin)
                ->where('status', Constants::INVOICE_STATUS_PAID);

            return $query->select(
                DB::raw('DATE_FORMAT(issue_date, "%Y-%m") as month'),
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(total_amount) as total_amount')
            )
                ->where('issue_date', '>=', now()->subMonths($months))
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->get();
        });
    }

    public function getRecentInvoices($userId = null, $isAdmin = false, $limit = 10)
    {
        $query = $this->getBaseQuery($userId, $isAdmin)
            ->with(['client' => function ($q) {
                $q->select('id', 'name', 'email');
            }])
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        return $query->get();
    }

    public function getOverdueInvoices($userId = null, $isAdmin = false)
    {
        return $this->getBaseQuery($userId, $isAdmin)
            ->whereIn('status', [Constants::INVOICE_STATUS_SENT, Constants::INVOICE_STATUS_OVERDUE])
            ->where('due_date', '<', now())
            ->with(['client' => function ($q) {
                $q->select('id', 'name', 'email', 'phone');
            }])
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public function getClientStats($userId = null, $isAdmin = false)
    {
        $cacheKey = 'client_stats_' . ($userId ?? 'all') . '_' . ($isAdmin ? 'admin' : 'user');

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($userId, $isAdmin) {
            $clientQuery = Client::query();

            if (!$isAdmin && $userId) {
                $clientQuery->where('user_id', $userId);
            }

            return [
                'total_clients' => $clientQuery->count(),
                'active_clients' => $clientQuery->has('invoices')->count(),
                'recent_clients' => $clientQuery->withCount(['invoices'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(),
            ];
        });
    }

    public function getUserStats($userId, $isAdmin = false)
    {
        if ($isAdmin) {
            return [
                'total_users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'admin_users' => User::whereIn('admin_group_id', [
                    Constants::SUPER_ADMIN_GROUP_ID,
                    Constants::ADMIN_GROUP_ID
                ])->count(),
                'client_users' => User::where('admin_group_id', Constants::CLIENT_GROUP_ID)->count(),
            ];
        }

        return $this->getDashboardStats($userId, false);
    }

    private function getBaseQuery($userId = null, $isAdmin = false)
    {
        $query = Invoice::query();

        if (!$isAdmin && $userId) {
            $query->where('user_id', $userId);
        }

        return $query;
    }

    public function clearCache($userId = null, $isAdmin = false)
    {
        Cache::forget('dashboard_stats_' . ($userId ?? 'all') . '_' . ($isAdmin ? 'admin' : 'user'));
        Cache::forget('monthly_revenue_' . ($userId ?? 'all') . '_' . ($isAdmin ? 'admin' : 'user'));
        Cache::forget('client_stats_' . ($userId ?? 'all') . '_' . ($isAdmin ? 'admin' : 'user'));
    }
}
