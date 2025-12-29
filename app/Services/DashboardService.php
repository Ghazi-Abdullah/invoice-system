<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getDashboardData($user)
    {
        // الحصول على البيانات بناءً على صلاحيات المستخدم
        $data = [];

        // صلاحيات عرض الفواتير
        if ($this->hasPermission($user, 'view_invoices')) {
            $data['stats']['totalInvoices'] = Invoice::count();
            $data['stats']['paidInvoices'] = Invoice::where('status', 'paid')->count();
            $data['stats']['invoiceGrowth'] = $this->calculateGrowth('invoices');
            $data['stats']['paymentRate'] = $this->calculatePaymentRate();
            $data['stats']['thisMonthInvoices'] = Invoice::whereMonth('created_at', now()->month)->count();
            $data['stats']['averageInvoice'] = Invoice::avg('total') ?? 0;
            $data['recentInvoices'] = Invoice::with('client')
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'client_name' => $invoice->client?->name,
                        'total' => $invoice->total,
                        'status' => $invoice->status,
                        'due_date' => $invoice->due_date
                    ];
                });
        }

        // صلاحيات عرض العملاء
        if ($this->hasPermission($user, 'view_clients')) {
            $data['stats']['totalClients'] = Client::count();
            $data['stats']['clientsGrowth'] = $this->calculateGrowth('clients');
            $data['stats']['newClientsThisMonth'] = Client::whereMonth('created_at', now()->month)->count();
            $data['recentClients'] = Client::latest()
                ->take(5)
                ->get()
                ->map(function ($client) {
                    return [
                        'id' => $client->id,
                        'name' => $client->name,
                        'email' => $client->email,
                        'created_at' => $client->created_at
                    ];
                });
        }

        // صلاحيات عرض التقارير
        if ($this->hasPermission($user, 'view_sales_report')) {
            $data['stats']['revenue'] = Invoice::where('status', 'paid')->sum('total');
            $data['stats']['revenueGrowth'] = $this->calculateRevenueGrowth();
            $data['stats']['collectionRate'] = $this->calculateCollectionRate();
        }

        // حساب معدل الدفع
        $data['stats']['paymentRate'] = $this->calculatePaymentRate();

        return $data;
    }

    /**
     * التحقق من صلاحية المستخدم
     */
    private function hasPermission($user, $permission)
    {
        // إذا كان مدير عام
        if ($user->admin_group_id == config('constants.SUPER_ADMIN_GROUP_ID')) {
            return true;
        }

        // التحقق من صلاحية المجموعة
        if ($user->adminGroup && $user->adminGroup->permissions) {
            return $user->adminGroup->permissions
                ->where('is_active', true)
                ->where('title', $permission)
                ->isNotEmpty();
        }

        return false;
    }

    /**
     * حساب النمو
     */
    private function calculateGrowth($type)
    {
        $currentMonth = now()->month;
        $previousMonth = now()->subMonth()->month;
        $year = now()->year;

        if ($type === 'invoices') {
            $current = Invoice::whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $year)
                ->count();
            $previous = Invoice::whereMonth('created_at', $previousMonth)
                ->whereYear('created_at', $year)
                ->count();
        } else {
            $current = Client::whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $year)
                ->count();
            $previous = Client::whereMonth('created_at', $previousMonth)
                ->whereYear('created_at', $year)
                ->count();
        }

        if ($previous == 0) return $current > 0 ? 100 : 0;

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * حساب معدل الدفع
     */
    private function calculatePaymentRate()
    {
        $totalInvoices = Invoice::count();
        $paidInvoices = Invoice::where('status', 'paid')->count();

        if ($totalInvoices == 0) return 0;

        return round(($paidInvoices / $totalInvoices) * 100, 2);
    }

    /**
     * حساب معدل التحصيل
     */
    private function calculateCollectionRate()
    {
        $totalAmount = Invoice::sum('total');
        $paidAmount = Invoice::where('status', 'paid')->sum('total');

        if ($totalAmount == 0) return 0;

        return round(($paidAmount / $totalAmount) * 100, 2);
    }

    /**
     * حساب نمو الإيرادات
     */
    private function calculateRevenueGrowth()
    {
        $currentMonth = now()->month;
        $previousMonth = now()->subMonth()->month;
        $year = now()->year;

        $currentRevenue = Invoice::where('status', 'paid')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $year)
            ->sum('total');

        $previousRevenue = Invoice::where('status', 'paid')
            ->whereMonth('created_at', $previousMonth)
            ->whereYear('created_at', $year)
            ->sum('total');

        if ($previousRevenue == 0) return $currentRevenue > 0 ? 100 : 0;

        return round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2);
    }
}
