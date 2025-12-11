<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function salesReport(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $query = Invoice::query();

        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $invoices = $query->with('client')->paginate(20);

        $totalAmount = $query->sum('total_amount');
        $totalPaid = $query->sum('paid_amount');
        $totalDue = $totalAmount - $totalPaid;

        return response()->json([
            'status' => true,
            'message' => 'Sales report retrieved successfully',
            'data' => [
                'invoices' => $invoices,
                'summary' => [
                    'total_amount' => $totalAmount,
                    'total_paid' => $totalPaid,
                    'total_due' => $totalDue,
                    'invoice_count' => $invoices->total()
                ]
            ]
        ]);
    }

    public function export(Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Export functionality will be implemented soon'
        ]);
    }
}
