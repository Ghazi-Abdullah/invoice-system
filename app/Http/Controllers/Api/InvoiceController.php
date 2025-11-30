<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        try {
            Log::info('Fetching invoices for user: ' . $request->user()->id, [
                'filters' => $request->all()
            ]);

            $query = Invoice::where('user_id', $request->user()->id)
                ->with(['client', 'items']);

            // تطبيق الفلاتر
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            if ($request->has('client_id') && $request->client_id !== '') {
                $query->where('client_id', $request->client_id);
            }

            if ($request->has('search') && $request->search !== '') {
                $query->where(function ($q) use ($request) {
                    $q->where('invoice_number', 'like', '%' . $request->search . '%')
                      ->orWhereHas('client', function ($clientQuery) use ($request) {
                          $clientQuery->where('name', 'like', '%' . $request->search . '%')
                                     ->orWhere('email', 'like', '%' . $request->search . '%');
                      });
                });
            }

            // فلترة حسب التاريخ
            if ($request->has('date') && $request->date !== '') {
                $this->applyDateFilter($query, $request->date);
            }

            $invoices = $query->latest()->paginate(10);

            Log::info('Successfully fetched ' . $invoices->count() . ' invoices');

            return response()->json([
                'success' => true,
                'data' => $invoices
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching invoices: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch invoices: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Creating invoice for user: ' . $request->user()->id, [
                'data' => $request->all()
            ]);

            $validator = Validator::make($request->all(), [
                'client_id' => 'required|exists:clients,id',
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after:issue_date',
                'items' => 'required|array|min:1',
                'items.*.description' => 'required|string|max:255',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'required|numeric|min:0',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // إنشاء رقم فاتورة
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(uniqid());

            // حساب المجاميع
            $subtotal = collect($request->items)->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });

            $taxAmount = $subtotal * 0.15;
            $totalAmount = $subtotal + $taxAmount;

            // إنشاء الفاتورة
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'client_id' => $request->client_id,
                'user_id' => $request->user()->id,
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'status' => 'draft',
                'notes' => $request->notes,
            ]);

            // إنشاء عناصر الفاتورة
            foreach ($request->items as $item) {
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            // تحميل العلاقات
            $invoice->load(['client', 'items']);

            Log::info('Invoice created successfully: ' . $invoice->id);

            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'data' => $invoice
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating invoice: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Invoice $invoice)
    {
        try {
            $invoice->load(['client', 'items', 'user']);

            return response()->json([
                'success' => true,
                'data' => $invoice
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching invoice: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Invoice $invoice)
    {
        try {
            $validator = Validator::make($request->all(), [
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after:issue_date',
                'items' => 'required|array|min:1',
                'items.*.description' => 'required|string|max:255',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'required|numeric|min:0',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // حذف العناصر القديمة
            $invoice->items()->delete();

            // حساب المجاميع الجديدة
            $subtotal = collect($request->items)->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });

            $taxAmount = $subtotal * 0.15;
            $totalAmount = $subtotal + $taxAmount;

            // تحديث الفاتورة
            $invoice->update([
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
            ]);

            // إضافة العناصر الجديدة
            foreach ($request->items as $item) {
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            $invoice->load(['client', 'items', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully',
                'data' => $invoice
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating invoice: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Invoice $invoice)
    {
        try {
            $invoice->delete();

            return response()->json([
                'success' => true,
                'message' => 'Invoice deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting invoice: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:draft,sent,paid,overdue'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $invoice->update(['status' => $request->status]);

            $invoice->load(['client', 'items', 'user']);

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Invoice status updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating invoice status: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * دالة مساعدة لفلترة التاريخ
     */
    private function applyDateFilter($query, $dateFilter)
    {
        $now = now();

        switch ($dateFilter) {
            case 'this_month':
                $query->whereYear('issue_date', $now->year)
                      ->whereMonth('issue_date', $now->month);
                break;

            case 'last_month':
                $lastMonth = $now->subMonth();
                $query->whereYear('issue_date', $lastMonth->year)
                      ->whereMonth('issue_date', $lastMonth->month);
                break;

            case 'this_year':
                $query->whereYear('issue_date', $now->year);
                break;

            case 'last_year':
                $query->whereYear('issue_date', $now->subYear()->year);
                break;
        }
    }
}
