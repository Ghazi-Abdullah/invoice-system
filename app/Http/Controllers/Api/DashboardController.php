<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function getStats(Request $request)
    {
        $user = Auth::user();

        $stats = [
            'totalClients' => 0,
            'totalInvoices' => 0,
            'paidInvoices' => 0,
            'revenue' => 0,
            'thisMonthInvoices' => 0,
            'newClientsThisMonth' => 0,
            'averageInvoice' => 0,
            'collectionRate' => 0,
            'clientsGrowth' => 0,
            'paymentRate' => 0,
        ];

        // إحصائيات العملاء
        if ($user->hasPermission('view_clients')) {
            $stats['totalClients'] = Client::count();

            $startOfMonth = now()->startOfMonth();
            $stats['newClientsThisMonth'] = Client::where('created_at', '>=', $startOfMonth)->count();

            // حساب النمو
            $lastMonthCount = Client::whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ])->count();

            $stats['clientsGrowth'] = $lastMonthCount > 0
                ? round(($stats['newClientsThisMonth'] / $lastMonthCount) * 100, 2)
                : 100;
        }

        // إحصائيات الفواتير
        if ($user->hasPermission('view_invoices')) {
            $stats['totalInvoices'] = Invoice::count();
            $stats['paidInvoices'] = Invoice::where('status', 'paid')->count();
            $stats['revenue'] = Invoice::sum('total_amount');

            $startOfMonth = now()->startOfMonth();
            $stats['thisMonthInvoices'] = Invoice::where('created_at', '>=', $startOfMonth)->count();

            $stats['paymentRate'] = $stats['totalInvoices'] > 0
                ? round(($stats['paidInvoices'] / $stats['totalInvoices']) * 100, 2)
                : 0;

            $stats['averageInvoice'] = $stats['totalInvoices'] > 0
                ? round($stats['revenue'] / $stats['totalInvoices'], 2)
                : 0;

            $stats['collectionRate'] = $stats['paymentRate'];
        }

        return response()->json([
            'status' => true,
            'message' => 'تم جلب الإحصائيات',
            'data' => $stats
        ]);
    }

    public function getRecentData(Request $request)
    {
        $user = Auth::user();

        $data = [
            'recentClients' => [],
            'recentInvoices' => []
        ];

        // العملاء الأخيرة
        if ($user->hasPermission('view_clients')) {
            $data['recentClients'] = Client::with('invoices')
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($client) {
                    return [
                        'id' => $client->id,
                        'name' => $client->name,
                        'email' => $client->email,
                        'phone' => $client->phone,
                        'address' => $client->address,
                        'created_at' => $client->created_at,
                        'updated_at' => $client->updated_at,
                        'total_invoices' => $client->invoices->count(),
                        'total_amount' => $client->invoices->sum('total_amount')
                    ];
                });
        }

        // الفواتير الأخيرة
        if ($user->hasPermission('view_invoices')) {
            $data['recentInvoices'] = Invoice::with('client')
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'client_id' => $invoice->client_id,
                        'client' => $invoice->client ? [
                            'id' => $invoice->client->id,
                            'name' => $invoice->client->name
                        ] : null,
                        'issue_date' => $invoice->issue_date,
                        'due_date' => $invoice->due_date,
                        'total_amount' => $invoice->total_amount,
                        'status' => $invoice->status,
                        'created_at' => $invoice->created_at,
                        'updated_at' => $invoice->updated_at
                    ];
                });
        }

        return response()->json([
            'status' => true,
            'message' => 'تم جلب البيانات الحديثة',
            'data' => $data
        ]);
    }
}
