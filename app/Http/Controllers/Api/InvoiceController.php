<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $userId = $request->user()->id;

            Log::info('🧾 جلب الفواتير للمستخدم:', [
                'user_id' => $userId,
                'email' => $request->user()->email
            ]);

            // استعلام لجميع الفواتير الخاصة بالمستخدم الحالي
            $query = Invoice::where('user_id', $userId)
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            Log::info('🔄 إنشاء فاتورة جديدة:', [
                'user_id' => $request->user()->id,
                'data' => $request->all()
            ]);

            $validator = Validator::make($request->all(), [
                'client_id' => 'required|exists:clients,id',
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                'items' => 'required|array|min:1',
                'items.*.description' => 'required|string|max:255',
                'items.*.quantity' => 'required|numeric|min:0.01|max:999999.99',
                'items.*.unit_price' => 'required|numeric|min:0|max:999999.99',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 422);
            }

            // التحقق من أن العميل يخص المستخدم
            $client = Client::where('id', $request->client_id)
                          ->where('user_id', $request->user()->id)
                          ->first();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'العميل غير موجود أو غير مسموح به'
                ], 403);
            }

            // إنشاء رقم فاتورة
            $lastInvoice = Invoice::where('user_id', $request->user()->id)
                                ->orderBy('id', 'desc')
                                ->first();

            $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad(
                $lastInvoice ? ($lastInvoice->id + 1) : 1,
                4, '0', STR_PAD_LEFT
            );

            // حساب المجاميع
            $subtotal = 0;
            foreach ($request->items as $item) {
                $itemTotal = round($item['quantity'] * $item['unit_price'], 2);
                $subtotal += $itemTotal;
            }

            $taxAmount = round($subtotal * 0.15, 2);
            $totalAmount = round($subtotal + $taxAmount, 2);

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
                $itemTotal = round($item['quantity'] * $item['unit_price'], 2);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $itemTotal,
                ]);
            }

            // تحميل العلاقات
            $invoice->load(['client', 'items']);

            DB::commit();

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
            DB::rollBack();

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

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        try {
            $invoice = Invoice::with(['client', 'items'])->find($id);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'الفاتورة غير موجودة'
                ], 404);
            }

            // التحقق من أن الفاتورة تخص المستخدم الحالي
            if ($request->user()->id !== $invoice->user_id) {
                Log::warning('⚠️ محاولة وصول غير مصرح للفاتورة:', [
                    'invoice_user_id' => $invoice->user_id,
                    'current_user_id' => $request->user()->id
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول لهذه الفاتورة'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $invoice
            ]);
        } catch (\Exception $e) {
            Log::error('❌ خطأ في جلب الفاتورة: ' . $e->getMessage(), [
                'invoice_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب الفاتورة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $invoice = Invoice::find($id);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'الفاتورة غير موجودة'
                ], 404);
            }

            // التحقق من أن الفاتورة تخص المستخدم الحالي
            if ($request->user()->id !== $invoice->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بتحديث هذه الفاتورة'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                'items' => 'required|array|min:1',
                'items.*.description' => 'required|string|max:255',
                'items.*.quantity' => 'required|numeric|min:0.01|max:999999.99',
                'items.*.unit_price' => 'required|numeric|min:0|max:999999.99',
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
            $subtotal = 0;
            foreach ($request->items as $item) {
                $itemTotal = round($item['quantity'] * $item['unit_price'], 2);
                $subtotal += $itemTotal;
            }

            $taxAmount = round($subtotal * 0.15, 2);
            $totalAmount = round($subtotal + $taxAmount, 2);

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
                $itemTotal = round($item['quantity'] * $item['unit_price'], 2);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $itemTotal,
                ]);
            }

            $invoice->load(['client', 'items']);

            DB::commit();

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
            DB::rollBack();

            Log::error('❌ خطأ في تحديث الفاتورة: ' . $e->getMessage(), [
                'invoice_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في تحديث الفاتورة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $invoice = Invoice::find($id);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'الفاتورة غير موجودة'
                ], 404);
            }

            // التحقق من أن الفاتورة تخص المستخدم الحالي
            if ($request->user()->id !== $invoice->user_id) {
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
                'invoice_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في حذف الفاتورة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update invoice status.
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $invoice = Invoice::find($id);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'الفاتورة غير موجودة'
                ], 404);
            }

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

            $invoice->load(['client', 'items']);

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
                'invoice_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في تحديث حالة الفاتورة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dashboard statistics.
     */
    public function dashboardStats(Request $request)
    {
        try {
            $userId = $request->user()->id;

            $stats = [
                'total_invoices' => Invoice::where('user_id', $userId)->count(),
                'total_clients' => Client::where('user_id', $userId)->count(),
                'total_paid' => Invoice::where('user_id', $userId)
                    ->where('status', 'paid')
                    ->sum('total_amount'),
                'total_pending' => Invoice::where('user_id', $userId)
                    ->whereIn('status', ['draft', 'sent'])
                    ->sum('total_amount'),
                'total_overdue' => Invoice::where('user_id', $userId)
                    ->where('status', 'overdue')
                    ->sum('total_amount'),
                'recent_invoices' => Invoice::where('user_id', $userId)
                    ->with('client')
                    ->latest()
                    ->limit(5)
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('❌ خطأ في جلب إحصائيات لوحة التحكم: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب الإحصائيات'
            ], 500);
        }
    }
}
