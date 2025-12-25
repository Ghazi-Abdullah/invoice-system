<?php

namespace App\Repository\User\Invoice;

use App\Models\Invoice;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Constants\Constants;

class InvoiceRepository implements InvoiceInterface
{
    public function index($filters = [])
    {
        $user = Auth::user();

        $query = Invoice::where('user_id', $user->id)
            ->with(['client', 'items']);

        // تطبيق الفلاتر
        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['client_id']) && $filters['client_id']) {
            $query->where('client_id', $filters['client_id']);
        }

        if (isset($filters['start_date']) && $filters['start_date']) {
            $query->whereDate('issue_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date']) && $filters['end_date']) {
            $query->whereDate('issue_date', '<=', $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function show(Invoice $invoice)
    {
        $user = Auth::user();

        // التأكد من أن الفاتورة تخص المستخدم
        if ($invoice->user_id !== $user->id) {
            throw new \Exception(__('messages.no_permission'));
        }

        return $invoice->load(['client', 'items']);
    }

    public function store($request)
    {
        $user = Auth::user();

        DB::beginTransaction();
        try {
            $data = $request->validated();

            // التأكد من أن العميل يخص المستخدم
            $client = Client::where('id', $data['client_id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$client) {
                throw new \Exception(__('messages.client_not_found_or_unauthorized'));
            }

            // حساب المجاميع
            $subtotal = 0;
            $tax_total = 0;

            foreach ($data['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $subtotal += $itemTotal;
                $tax_total += $itemTotal * ($item['tax_rate'] ?? 0) / 100;
            }

            $total_amount = $subtotal + $tax_total;

            // إنشاء الفاتورة
            $invoice = Invoice::create([
                'client_id' => $data['client_id'],
                'invoice_number' => $data['invoice_number'] ?? $this->generateInvoiceNumber(),
                'issue_date' => $data['invoice_date'],
                'due_date' => $data['due_date'],
                'subtotal' => $subtotal,
                'tax_total' => $tax_total,
                'total_amount' => $total_amount,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'status' => Constants::INVOICE_STATUS_DRAFT,
                'user_id' => $user->id,
            ]);

            // إضافة العناصر
            foreach ($data['items'] as $itemData) {
                $itemTotal = $itemData['quantity'] * $itemData['unit_price'];
                $invoice->items()->create([
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total' => $itemTotal,
                    'tax_rate' => $itemData['tax_rate'] ?? 0,
                    'item_type' => $itemData['item_type'] ?? 'service',
                ]);
            }

            DB::commit();
            return $invoice->load(['client', 'items']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__('messages.invoice_creation_failed') . ': ' . $e->getMessage());
        }
    }

    public function update($request, Invoice $invoice)
    {
        $user = Auth::user();

        // التأكد من أن الفاتورة تخص المستخدم
        if ($invoice->user_id !== $user->id) {
            throw new \Exception(__('messages.no_permission'));
        }

        DB::beginTransaction();
        try {
            $data = $request->validated();

            // إذا تم تحديث العميل، التأكد من أنه يخص المستخدم
            if (isset($data['client_id'])) {
                $client = Client::where('id', $data['client_id'])
                    ->where('user_id', $user->id)
                    ->first();

                if (!$client) {
                    throw new \Exception(__('messages.client_not_found_or_unauthorized'));
                }
            }

            // إذا تم تحديث العناصر
            if (isset($data['items'])) {
                // حذف العناصر القديمة
                $invoice->items()->delete();

                // حساب المجاميع الجديدة
                $subtotal = 0;
                $tax_total = 0;

                foreach ($data['items'] as $itemData) {
                    $itemTotal = $itemData['quantity'] * $itemData['unit_price'];
                    $subtotal += $itemTotal;
                    $tax_total += $itemTotal * ($itemData['tax_rate'] ?? 0) / 100;

                    // إضافة العنصر الجديد
                    $invoice->items()->create([
                        'description' => $itemData['description'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'total' => $itemTotal,
                        'tax_rate' => $itemData['tax_rate'] ?? 0,
                        'item_type' => $itemData['item_type'] ?? 'service',
                    ]);
                }

                // تحديث المجاميع
                $invoice->update([
                    'subtotal' => $subtotal,
                    'tax_total' => $tax_total,
                    'total_amount' => $subtotal + $tax_total,
                ]);
            }

            // تحديث باقي البيانات
            $invoice->update([
                'client_id' => $data['client_id'] ?? $invoice->client_id,
                'invoice_number' => $data['invoice_number'] ?? $invoice->invoice_number,
                'issue_date' => $data['invoice_date'] ?? $invoice->issue_date,
                'due_date' => $data['due_date'] ?? $invoice->due_date,
                'notes' => $data['notes'] ?? $invoice->notes,
                'terms' => $data['terms'] ?? $invoice->terms,
            ]);

            DB::commit();
            return $invoice->load(['client', 'items']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__('messages.invoice_update_failed') . ': ' . $e->getMessage());
        }
    }

    public function destroy(Invoice $invoice)
    {
        $user = Auth::user();

        // التأكد من أن الفاتورة تخص المستخدم
        if ($invoice->user_id !== $user->id) {
            throw new \Exception(__('messages.no_permission'));
        }

        DB::beginTransaction();
        try {
            // حذف العناصر أولاً
            $invoice->items()->delete();
            $invoice->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__('messages.invoice_delete_failed') . ': ' . $e->getMessage());
        }
    }

    public function updateStatus(Invoice $invoice, $status)
    {
        $user = Auth::user();

        // التأكد من أن الفاتورة تخص المستخدم
        if ($invoice->user_id !== $user->id) {
            throw new \Exception(__('messages.no_permission'));
        }

        $invoice->update(['status' => $status]);
        return $invoice;
    }

    public function getUserStats()
    {
        $user = Auth::user();

        $totalInvoices = Invoice::where('user_id', $user->id)->count();
        $totalAmount = Invoice::where('user_id', $user->id)->sum('total_amount');
        $paidInvoices = Invoice::where('user_id', $user->id)
            ->where('status', Constants::INVOICE_STATUS_PAID)->count();
        $paidAmount = Invoice::where('user_id', $user->id)
            ->where('status', Constants::INVOICE_STATUS_PAID)->sum('total_amount');
        $overdueInvoices = Invoice::where('user_id', $user->id)
            ->where('status', Constants::INVOICE_STATUS_OVERDUE)->count();

        return [
            'total_invoices' => $totalInvoices,
            'total_amount' => $totalAmount,
            'paid_invoices' => $paidInvoices,
            'paid_amount' => $paidAmount,
            'overdue_invoices' => $overdueInvoices,
            'average_invoice' => $totalInvoices > 0 ? round($totalAmount / $totalInvoices, 2) : 0,
            'payment_rate' => $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 2) : 0,
        ];
    }

    private function generateInvoiceNumber()
    {
        $year = date('Y');
        $month = date('m');
        $sequence = Invoice::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;

        return 'INV-' . $year . $month . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
