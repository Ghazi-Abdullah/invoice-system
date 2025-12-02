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
            $userId = $request->user()->id;

            Log::info('🧾 جلب الفواتير للمستخدم:', [
                'user_id' => $userId,
                'email' => $request->user()->email
            ]);

            // استعلام لجميع الفواتير الخاصة بالمستخدم الحالي بالإضافة إلى الفواتير الافتراضية
            $query = Invoice::where(function($q) use ($userId) {
                $q->where('user_id', $userId);
                 // ->orWhere('user_id', 1); // الفواتير الافتراضية
            })
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

            $invoices = $query->latest()->paginate(20);

            Log::info('📊 نتيجة جلب الفواتير:', [
                'total_invoices' => $invoices->total(),
                'current_count' => $invoices->count(),
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'data' => $invoices
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في جلب الفواتير: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id ?? 'غير معروف'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب الفواتير: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('🔄 إنشاء فاتورة جديدة:', [
                'user_id' => $request->user()->id,
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
                    'message' => 'فشل في التحقق من البيانات',
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

            Log::info('✅ تم إنشاء فاتورة جديدة:', [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'user_id' => $invoice->user_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الفاتورة بنجاح',
                'data' => $invoice
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في إنشاء الفاتورة: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id ?? 'غير معروف'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في إنشاء الفاتورة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Invoice $invoice)
    {
        try {
            // التحقق من أن الفاتورة تخص المستخدم الحالي أو هي فاتورة افتراضية
            if (request()->user()->id !== $invoice->user_id && $invoice->user_id !== 1) {
                Log::warning('⚠️ محاولة وصول غير مصرح للفاتورة:', [
                    'invoice_user_id' => $invoice->user_id,
                    'current_user_id' => request()->user()->id
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول لهذه الفاتورة'
                ], 403);
            }

            $invoice->load(['client', 'items', 'user']);

            return response()->json([
                'success' => true,
                'data' => $invoice
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في جلب الفاتورة: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب الفاتورة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Invoice $invoice)
    {
        try {
            // التحقق من أن الفاتورة تخص المستخدم الحالي (وليست افتراضية)
            if ($request->user()->id !== $invoice->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بتحديث هذه الفاتورة'
                ], 403);
            }

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
                    'message' => 'فشل في التحقق من البيانات',
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

            Log::info('✏️ تم تحديث الفاتورة:', [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الفاتورة بنجاح',
                'data' => $invoice
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في تحديث الفاتورة: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في تحديث الفاتورة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Invoice $invoice)
    {
        try {
            // التحقق من أن الفاتورة تخص المستخدم الحالي
            if (request()->user()->id !== $invoice->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بحذف هذه الفاتورة'
                ], 403);
            }

            $invoice->delete();

            Log::info('🗑️ تم حذف الفاتورة:', [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الفاتورة بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في حذف الفاتورة: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في حذف الفاتورة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        try {
            // التحقق من أن الفاتورة تخص المستخدم الحالي
            if ($request->user()->id !== $invoice->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بتحديث حالة هذه الفاتورة'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:draft,sent,paid,overdue'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 422);
            }

            $invoice->update(['status' => $request->status]);

            $invoice->load(['client', 'items', 'user']);

            Log::info('🔄 تم تحديث حالة الفاتورة:', [
                'id' => $invoice->id,
                'status' => $invoice->status
            ]);

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'تم تحديث حالة الفاتورة بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في تحديث حالة الفاتورة: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في تحديث حالة الفاتورة: ' . $e->getMessage()
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

    /**
     * دالة لربط الفواتير الافتراضية بالمستخدم الحالي
     */
    public function linkDefaultInvoices(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $defaultInvoices = Invoice::where('user_id', 1)->get();

            $linkedCount = 0;

            foreach ($defaultInvoices as $invoice) {
                // نسخ الفاتورة الافتراضية للمستخدم الحالي
                $newInvoice = $invoice->replicate();
                $newInvoice->user_id = $userId;
                $newInvoice->invoice_number = 'INV-' . date('Ymd') . '-' . strtoupper(uniqid());
                $newInvoice->save();

                // نسخ العناصر
                foreach ($invoice->items as $item) {
                    $newItem = $item->replicate();
                    $newItem->invoice_id = $newInvoice->id;
                    $newItem->save();
                }

                $linkedCount++;
            }

            Log::info('✅ تم ربط الفواتير الافتراضية:', [
                'user_id' => $userId,
                'count' => $linkedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم ربط ' . $linkedCount . ' فاتورة افتراضية بحسابك',
                'count' => $linkedCount
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في ربط الفواتير الافتراضية: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'فشل في ربط الفواتير الافتراضية'
            ], 500);
        }
    }
}
