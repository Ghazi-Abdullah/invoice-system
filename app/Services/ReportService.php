<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\User;
use App\Constants\Constants;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function generateInvoiceReport($filters)
    {
        $query = Invoice::with(['client', 'createdBy']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->whereBetween('invoice_date', [
                $filters['date_from'],
                $filters['date_to']
            ]);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // Get results
        if (isset($filters['paginate']) && $filters['paginate']) {
            $perPage = isset($filters['per_page']) ? $filters['per_page'] : Constants::DEFAULT_PER_PAGE;
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    public function generateClientReport($filters)
    {
        $query = Client::with(['invoices' => function($q) {
            $q->select('client_id', DB::raw('SUM(total) as total_invoiced'))
              ->groupBy('client_id');
        }]);

        // Apply filters
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // Calculate totals for each client
        $clients = $query->get()->map(function($client) {
            $client->total_invoiced = $client->invoices->sum('total_invoiced') ?? 0;
            $client->total_paid = $client->invoices()->where('status', Constants::INVOICE_STATUS_PAID)->sum('total');
            $client->total_due = $client->total_invoiced - $client->total_paid;

            return $client;
        });

        // Sort if needed
        if (isset($filters['sort_by'])) {
            $sortField = $filters['sort_by'];
            $sortDirection = isset($filters['sort_dir']) ? $filters['sort_dir'] : 'desc';

            $clients = $clients->sortBy($sortField, SORT_REGULAR, $sortDirection === 'desc');
        }

        // Get results
        if (isset($filters['paginate']) && $filters['paginate']) {
            $perPage = isset($filters['per_page']) ? $filters['per_page'] : Constants::DEFAULT_PER_PAGE;
            return new \Illuminate\Pagination\LengthAwarePaginator(
                $clients->forPage(request('page', 1), $perPage),
                $clients->count(),
                $perPage,
                request('page', 1)
            );
        }

        return $clients;
    }

    public function generatePaymentReport($filters)
    {
        $query = Invoice::with(['client'])
            ->where('status', Constants::INVOICE_STATUS_PAID);

        // Apply filters
        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->whereBetween('paid_at', [
                $filters['date_from'],
                $filters['date_to']
            ]);
        }

        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        // Group by period if requested
        if (isset($filters['group_by'])) {
            $groupBy = $filters['group_by'];

            switch ($groupBy) {
                case 'day':
                    $dateFormat = 'Y-m-d';
                    break;
                case 'week':
                    $dateFormat = 'Y-W';
                    break;
                case 'month':
                default:
                    $dateFormat = 'Y-m';
                    break;
            }

            $results = $query->selectRaw("
                DATE_FORMAT(paid_at, '{$dateFormat}') as period,
                COUNT(*) as payment_count,
                SUM(total) as total_paid
            ")
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get();

            return $results;
        }

        // Get results
        if (isset($filters['paginate']) && $filters['paginate']) {
            $perPage = isset($filters['per_page']) ? $filters['per_page'] : Constants::DEFAULT_PER_PAGE;
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    public function generateTaxReport($filters)
    {
        $query = Invoice::with(['client', 'items'])
            ->where('tax_amount', '>', 0);

        // Apply filters
        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->whereBetween('invoice_date', [
                $filters['date_from'],
                $filters['date_to']
            ]);
        }

        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        // Calculate tax summary
        $summary = $query->selectRaw("
            SUM(tax_amount) as total_tax,
            COUNT(*) as invoice_count,
            AVG(tax_amount) as average_tax
        ")->first();

        // Get detailed records
        $invoices = $query->get();

        return [
            'summary' => $summary,
            'invoices' => $invoices,
            'total_records' => $invoices->count()
        ];
    }

    public function getTopClients($limit = 10, $period = null)
    {
        $query = Client::withSum(['invoices as total_invoiced' => function($q) use ($period) {
            if ($period) {
                $q->where('invoice_date', '>=', now()->sub($period));
            }
        }], 'total')
        ->orderBy('total_invoiced', 'desc')
        ->limit($limit);

        return $query->get();
    }

    public function getMonthlyRevenue($year = null)
    {
        $year = $year ?? date('Y');

        $revenue = Invoice::selectRaw("
            MONTH(invoice_date) as month,
            YEAR(invoice_date) as year,
            SUM(total) as revenue,
            COUNT(*) as invoice_count
        ")
        ->whereYear('invoice_date', $year)
        ->groupBy('year', 'month')
        ->orderBy('year', 'asc')
        ->orderBy('month', 'asc')
        ->get();

        // Fill in missing months with zero revenue
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthData = $revenue->where('month', $i)->first();

            $months[] = [
                'month' => $i,
                'year' => $year,
                'month_name' => date('F', mktime(0, 0, 0, $i, 1)),
                'revenue' => $monthData ? $monthData->revenue : 0,
                'invoice_count' => $monthData ? $monthData->invoice_count : 0
            ];
        }

        return $months;
    }

    public function getRecentActivity($limit = 20)
    {
        // This would typically come from an ActivityLog model
        // For now, we'll return a placeholder or use the actual model if it exists

        if (class_exists('App\Models\ActivityLog')) {
            return \App\Models\ActivityLog::with('user')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        }

        return [];
    }
}
