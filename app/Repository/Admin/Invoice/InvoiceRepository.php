<?php

namespace App\Repository\Admin\Invoice;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ActivityLog;
use App\Constants\Constants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceRepository implements InvoiceInterface
{
    public function index($request)
    {
        try {
            $query = Invoice::with(['client', 'items', 'createdBy'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('invoice_date', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('invoice_date', '<=', $request->date_to);
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhereHas('client', function($client) use ($search) {
                          $client->where('name', 'like', "%{$search}%")
                                 ->orWhere('company_name', 'like', "%{$search}%");
                      });
                });
            }

            // Get paginated results
            $perPage = $request->per_page ?? Constants::DEFAULT_PER_PAGE;
            $perPage = min($perPage, Constants::MAX_PER_PAGE);
            $perPage = max($perPage, Constants::MIN_PER_PAGE);

            $invoices = $query->paginate($perPage);

            return [
                'status' => true,
                'message' => 'تم استرجاع الفواتير بنجاح',
                'data' => $invoices
            ];

        } catch (\Exception $e) {
            Log::error('InvoiceRepository index error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'status' => false,
                'message' => 'فشل في استرجاع الفواتير: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function show($id)
    {
        try {
            $invoice = Invoice::with(['client', 'items', 'createdBy'])->find($id);

            if (!$invoice) {
                return [
                    'status' => false,
                    'message' => 'الفاتورة غير موجودة',
                    'data' => null
                ];
            }

            return [
                'status' => true,
                'message' => 'تم استرجاع الفاتورة بنجاح',
                'data' => $invoice
            ];

        } catch (\Exception $e) {
            Log::error('InvoiceRepository show error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'فشل في استرجاع الفاتورة: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function store($request)
    {
        DB::beginTransaction();

        try {
            // توليد رقم فاتورة تلقائي إذا لم يتم تقديمه
            $invoiceNumber = $request->invoice_number;
            if (empty($invoiceNumber)) {
                $invoice = new Invoice();
                $invoiceNumber = $invoice->generateInvoiceNumber();
            }

            // التحقق من رقم الفاتورة الفريد
            $existingInvoice = Invoice::where('invoice_number', $invoiceNumber)->first();
            if ($existingInvoice) {
                return [
                    'status' => false,
                    'message' => 'رقم الفاتورة موجود مسبقاً',
                    'data' => null
                ];
            }

            // حساب المجاميع
            $subtotal = $request->subtotal ?? 0;
            $taxAmount = $request->tax_amount ?? 0;
            $discountAmount = $request->discount_amount ?? 0;
            $total = $subtotal + $taxAmount - $discountAmount;

            // إنشاء الفاتورة
            $invoice = Invoice::create([
                'client_id' => $request->client_id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'status' => $request->status ?? Constants::INVOICE_STATUS_DRAFT,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'currency' => $request->currency ?? Constants::CURRENCY_SAR,
                'notes' => $request->notes ?? null,
                'terms' => $request->terms ?? null,
                'footer' => $request->footer ?? null,
                'created_by' => auth()->id() ?? 1,
                'is_active' => true
            ]);

            // إضافة العناصر إذا كانت موجودة
            if ($request->has('items') && is_array($request->items)) {
                $itemsTotal = 0;
                foreach ($request->items as $item) {
                    $itemTotal = $item['quantity'] * $item['unit_price'];
                    $itemsTotal += $itemTotal;

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total' => $itemTotal,
                        'tax_rate' => $item['tax_rate'] ?? 0
                    ]);
                }

                // تحديث المجاميع بناءً على العناصر
                if ($itemsTotal > 0) {
                    $invoice->update([
                        'subtotal' => $itemsTotal,
                        'total' => $itemsTotal + $taxAmount - $discountAmount
                    ]);
                }
            }

            // تسجيل النشاط
            ActivityLog::log(
                'CREATE',
                'Created invoice: ' . $invoice->invoice_number,
                $invoice
            );

            DB::commit();

            return [
                'status' => true,
                'message' => 'تم إنشاء الفاتورة بنجاح',
                'data' => $invoice->load(['client', 'items', 'createdBy'])
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('InvoiceRepository store error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'فشل في إنشاء الفاتورة: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function update($request, $invoice)
    {
        DB::beginTransaction();

        try {
            $oldValues = $invoice->toArray();

            // التحقق من رقم الفاتورة الفريد (استثناء الفاتورة الحالية)
            if ($request->has('invoice_number') && $request->invoice_number !== $invoice->invoice_number) {
                $existingInvoice = Invoice::where('invoice_number', $request->invoice_number)
                    ->where('id', '!=', $invoice->id)
                    ->first();

                if ($existingInvoice) {
                    return [
                        'status' => false,
                        'message' => 'رقم الفاتورة موجود مسبقاً',
                        'data' => null
                    ];
                }
            }

            // حساب المجاميع
            $subtotal = $request->subtotal ?? $invoice->subtotal;
            $taxAmount = $request->tax_amount ?? $invoice->tax_amount;
            $discountAmount = $request->discount_amount ?? $invoice->discount_amount;
            $total = $subtotal + $taxAmount - $discountAmount;

            // تحديث بيانات الفاتورة
            $updateData = [
                'client_id' => $request->client_id ?? $invoice->client_id,
                'invoice_number' => $request->invoice_number ?? $invoice->invoice_number,
                'invoice_date' => $request->invoice_date ?? $invoice->invoice_date,
                'due_date' => $request->due_date ?? $invoice->due_date,
                'status' => $request->status ?? $invoice->status,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'currency' => $request->currency ?? $invoice->currency,
                'notes' => $request->notes ?? $invoice->notes,
                'terms' => $request->terms ?? $invoice->terms,
                'footer' => $request->footer ?? $invoice->footer,
                'is_active' => $request->has('is_active') ? (bool)$request->is_active : $invoice->is_active
            ];

            $invoice->update($updateData);

            // تحديث العناصر إذا كانت موجودة
            if ($request->has('items') && is_array($request->items)) {
                // حذف العناصر القديمة
                $invoice->items()->delete();

                // إضافة العناصر الجديدة
                $itemsTotal = 0;
                foreach ($request->items as $item) {
                    $itemTotal = $item['quantity'] * $item['unit_price'];
                    $itemsTotal += $itemTotal;

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total' => $itemTotal,
                        'tax_rate' => $item['tax_rate'] ?? 0
                    ]);
                }

                // تحديث المجاميع بناءً على العناصر
                if ($itemsTotal > 0) {
                    $invoice->update([
                        'subtotal' => $itemsTotal,
                        'total' => $itemsTotal + $taxAmount - $discountAmount
                    ]);
                }
            }

            // تسجيل النشاط
            ActivityLog::log(
                'UPDATE',
                'Updated invoice: ' . $invoice->invoice_number,
                $invoice,
                $oldValues,
                $invoice->fresh()->toArray()
            );

            DB::commit();

            return [
                'status' => true,
                'message' => 'تم تحديث الفاتورة بنجاح',
                'data' => $invoice->load(['client', 'items', 'createdBy'])
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('InvoiceRepository update error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'فشل في تحديث الفاتورة: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function destroy($invoice)
    {
        DB::beginTransaction();

        try {
            $invoiceNumber = $invoice->invoice_number;
            $invoiceId = $invoice->id;

            // تسجيل النشاط قبل الحذف
            ActivityLog::log(
                'DELETE',
                'Deleted invoice: ' . $invoiceNumber,
                $invoice
            );

            // حذف العناصر أولاً
            $invoice->items()->delete();

            // حذف الفاتورة
            $invoice->delete();

            DB::commit();

            return [
                'status' => true,
                'message' => 'تم حذف الفاتورة بنجاح',
                'data' => ['id' => $invoiceId]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('InvoiceRepository destroy error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'فشل في حذف الفاتورة: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function sendInvoice($invoice)
    {
        try {
            $invoice->markAsSent();

            ActivityLog::log(
                'UPDATE',
                'Sent invoice: ' . $invoice->invoice_number,
                $invoice
            );

            return [
                'status' => true,
                'message' => 'تم إرسال الفاتورة بنجاح',
                'data' => $invoice->load(['client', 'items', 'createdBy'])
            ];

        } catch (\Exception $e) {
            Log::error('InvoiceRepository sendInvoice error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'فشل في إرسال الفاتورة: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function markAsPaid($invoice)
    {
        try {
            $invoice->markAsPaid();

            ActivityLog::log(
                'UPDATE',
                'Marked invoice as paid: ' . $invoice->invoice_number,
                $invoice
            );

            return [
                'status' => true,
                'message' => 'تم تعليم الفاتورة كمدفوعة بنجاح',
                'data' => $invoice->load(['client', 'items', 'createdBy'])
            ];

        } catch (\Exception $e) {
            Log::error('InvoiceRepository markAsPaid error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'فشل في تعليم الفاتورة كمدفوعة: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function duplicate($invoice)
    {
        DB::beginTransaction();

        try {
            // إنشاء نسخة من الفاتورة
            $newInvoice = $invoice->replicate();
            $newInvoice->invoice_number = $invoice->generateInvoiceNumber();
            $newInvoice->status = Constants::INVOICE_STATUS_DRAFT;
            $newInvoice->sent_at = null;
            $newInvoice->paid_at = null;
            $newInvoice->created_by = auth()->id() ?? 1;
            $newInvoice->save();

            // نسخ العناصر
            foreach ($invoice->items as $item) {
                $newItem = $item->replicate();
                $newItem->invoice_id = $newInvoice->id;
                $newItem->save();
            }

            // تسجيل النشاط
            ActivityLog::log(
                'CREATE',
                'Duplicated invoice: ' . $invoice->invoice_number . ' to ' . $newInvoice->invoice_number,
                $newInvoice
            );

            DB::commit();

            return [
                'status' => true,
                'message' => 'تم نسخ الفاتورة بنجاح',
                'data' => $newInvoice->load(['client', 'items', 'createdBy'])
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('InvoiceRepository duplicate error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'فشل في نسخ الفاتورة: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function generatePDF($invoice)
    {
        try {
            // هنا يمكنك إضافة كود إنشاء PDF
            // هذا مثال بسيط للعودة
            $filePath = 'invoices/invoice_' . $invoice->invoice_number . '.pdf';

            return [
                'status' => true,
                'message' => 'تم إنشاء ملف PDF',
                'data' => [
                    'file_path' => $filePath,
                    'file_name' => 'invoice_' . $invoice->invoice_number . '.pdf'
                ]
            ];

        } catch (\Exception $e) {
            Log::error('InvoiceRepository generatePDF error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'فشل في إنشاء ملف PDF: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getDashboardStats()
    {
        try {
            $totalInvoices = Invoice::count();
            $totalAmount = Invoice::sum('total');
            $paidInvoices = Invoice::where('status', Constants::INVOICE_STATUS_PAID)->count();
            $paidAmount = Invoice::where('status', Constants::INVOICE_STATUS_PAID)->sum('total');
            $overdueInvoices = Invoice::where('status', Constants::INVOICE_STATUS_OVERDUE)->count();
            $overdueAmount = Invoice::where('status', Constants::INVOICE_STATUS_OVERDUE)->sum('total');

            $stats = [
                'total_invoices' => $totalInvoices,
                'total_amount' => $totalAmount,
                'paid_invoices' => $paidInvoices,
                'paid_amount' => $paidAmount,
                'overdue_invoices' => $overdueInvoices,
                'overdue_amount' => $overdueAmount,
                'currency' => Constants::CURRENCY_SAR
            ];

            return [
                'status' => true,
                'message' => 'تم استرجاع إحصائيات لوحة التحكم',
                'data' => $stats
            ];

        } catch (\Exception $e) {
            Log::error('InvoiceRepository getDashboardStats error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'فشل في استرجاع الإحصائيات: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getRecentInvoices($limit = 10)
    {
        try {
            $invoices = Invoice::with(['client'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return [
                'status' => true,
                'message' => 'تم استرجاع الفواتير الأخيرة',
                'data' => $invoices
            ];

        } catch (\Exception $e) {
            Log::error('InvoiceRepository getRecentInvoices error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'فشل في استرجاع الفواتير الأخيرة: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getOverdueInvoices()
    {
        try {
            $invoices = Invoice::with(['client'])
                ->where('status', Constants::INVOICE_STATUS_OVERDUE)
                ->orWhere(function($query) {
                    $query->where('status', Constants::INVOICE_STATUS_SENT)
                          ->where('due_date', '<', now());
                })
                ->orderBy('due_date', 'asc')
                ->get();

            return [
                'status' => true,
                'message' => 'تم استرجاع الفواتير المتأخرة',
                'data' => $invoices
            ];

        } catch (\Exception $e) {
            Log::error('InvoiceRepository getOverdueInvoices error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'فشل في استرجاع الفواتير المتأخرة: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
