<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator; // تصحيح الاستيراد
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'يجب تسجيل الدخول أولاً'
                ], 401);
            }

            if (!$user->hasPermission('view_invoices')) {
                Log::warning('User does not have view_invoices permission', ['user_id' => $user->id]);
                return response()->json([
                    'status' => false,
                    'message' => 'ليس لديك صلاحية لعرض الفواتير'
                ], 403);
            }

            Log::info('Fetching invoices for user', [
                'user_id' => $user->id,
                'admin_group_id' => $user->admin_group_id,
                'is_admin' => $user->admin_group_id == 1
            ]);

            $invoices = Invoice::with(['client', 'items']);

            // الفلاتر
            if ($request->has('status') && $request->status) {
                $invoices->where('status', $request->status);
            }

            if ($request->has('client_id') && $request->client_id) {
                $invoices->where('client_id', $request->client_id);
            }

            if ($request->has('search') && $request->search) {
                $invoices->where(function ($query) use ($request) {
                    $query->where('invoice_number', 'like', '%' . $request->search . '%')
                          ->orWhereHas('client', function ($q) use ($request) {
                              $q->where('name', 'like', '%' . $request->search . '%')
                                ->orWhere('email', 'like', '%' . $request->search . '%');
                          });
                });
            }

            // الترتيب
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $invoices->orderBy($sortBy, $sortOrder);

            // الصفحة
            $perPage = $request->get('per_page', 15);
            $result = $invoices->paginate($perPage);

            Log::info('Invoices fetched successfully', [
                'total' => $result->total(),
                'count' => $result->count()
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم جلب الفواتير بنجاح',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching invoices: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ في جلب الفواتير: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'يجب تسجيل الدخول أولاً'
                ], 401);
            }

            if (!$user->hasPermission('view_invoices')) {
                return response()->json([
                    'status' => false,
                    'message' => 'ليس لديك صلاحية لعرض الفواتير'
                ], 403);
            }

            $invoice = Invoice::with(['client', 'items'])->find($id);

            if (!$invoice) {
                return response()->json([
                    'status' => false,
                    'message' => 'الفاتورة غير موجودة'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'تم جلب الفاتورة بنجاح',
                'data' => $invoice
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching invoice: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ في جلب الفاتورة'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'يجب تسجيل الدخول أولاً'
                ], 401);
            }

            if (!$user->hasPermission('create_invoice')) {
                Log::warning('User does not have create_invoice permission', ['user_id' => $user->id]);
                return response()->json([
                    'status' => false,
                    'message' => 'ليس لديك صلاحية لإنشاء فواتير'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'client_id' => 'required|exists:clients,id',
                'invoice_number' => 'required|string|unique:invoices',
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                'items' => 'required|array|min:1',
                'items.*.description' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
                'notes' => 'nullable|string',
                'terms' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في التحقق',
                    'errors' => $validator->errors()
                ], 422);
            }

            // حساب المجموع
            $subtotal = 0;
            $tax_total = 0;

            foreach ($request->items as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $subtotal += $itemTotal;

                $taxRate = $item['tax_rate'] ?? 0;
                $tax_total += $itemTotal * ($taxRate / 100);
            }

            $total_amount = $subtotal + $tax_total;

            // إنشاء الفاتورة
            $invoice = Invoice::create([
                'client_id' => $request->client_id,
                'invoice_number' => $request->invoice_number,
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'subtotal' => $subtotal,
                'tax_total' => $tax_total,
                'total_amount' => $total_amount,
                'status' => 'draft',
                'notes' => $request->notes,
                'terms' => $request->terms,
                'user_id' => $user->id
            ]);

            // إضافة العناصر
            foreach ($request->items as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $itemTotal,
                    'tax_rate' => $item['tax_rate'] ?? 0
                ]);
            }

            $invoice->load(['client', 'items']);

            Log::info('Invoice created successfully', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'client_id' => $invoice->client_id,
                'total_amount' => $invoice->total_amount
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء الفاتورة بنجاح',
                'data' => $invoice
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating invoice: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ في إنشاء الفاتورة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'يجب تسجيل الدخول أولاً'
                ], 401);
            }

            if (!$user->hasPermission('edit_invoice')) {
                Log::warning('User does not have edit_invoice permission', ['user_id' => $user->id]);
                return response()->json([
                    'status' => false,
                    'message' => 'ليس لديك صلاحية لتعديل الفواتير'
                ], 403);
            }

            $invoice = Invoice::find($id);

            if (!$invoice) {
                return response()->json([
                    'status' => false,
                    'message' => 'الفاتورة غير موجودة'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'client_id' => 'sometimes|exists:clients,id',
                'invoice_number' => 'sometimes|string|unique:invoices,invoice_number,' . $id,
                'issue_date' => 'sometimes|date',
                'due_date' => 'sometimes|date|after_or_equal:issue_date',
                'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled',
                'notes' => 'nullable|string',
                'terms' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في التحقق',
                    'errors' => $validator->errors()
                ], 422);
            }

            // إذا تم تحديث العناصر
            if ($request->has('items') && is_array($request->items)) {
                // حذف العناصر القديمة
                $invoice->items()->delete();

                // حساب المجموع الجديد
                $subtotal = 0;
                $tax_total = 0;

                foreach ($request->items as $item) {
                    $itemTotal = $item['quantity'] * $item['unit_price'];
                    $subtotal += $itemTotal;

                    $taxRate = $item['tax_rate'] ?? 0;
                    $tax_total += $itemTotal * ($taxRate / 100);

                    // إضافة العنصر الجديد
                    $invoice->items()->create([
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total' => $itemTotal,
                        'tax_rate' => $item['tax_rate'] ?? 0
                    ]);
                }

                $total_amount = $subtotal + $tax_total;

                // تحديث المجاميع
                $invoice->update([
                    'subtotal' => $subtotal,
                    'tax_total' => $tax_total,
                    'total_amount' => $total_amount
                ]);
            }

            $invoice->update($request->only([
                'client_id', 'invoice_number', 'issue_date', 'due_date', 'status', 'notes', 'terms'
            ]));

            $invoice->load(['client', 'items']);

            Log::info('Invoice updated successfully', ['invoice_id' => $invoice->id]);

            return response()->json([
                'status' => true,
                'message' => 'تم تحديث الفاتورة بنجاح',
                'data' => $invoice
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating invoice: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ في تحديث الفاتورة'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'يجب تسجيل الدخول أولاً'
                ], 401);
            }

            if (!$user->hasPermission('delete_invoice')) {
                Log::warning('User does not have delete_invoice permission', ['user_id' => $user->id]);
                return response()->json([
                    'status' => false,
                    'message' => 'ليس لديك صلاحية لحذف الفواتير'
                ], 403);
            }

            $invoice = Invoice::find($id);

            if (!$invoice) {
                return response()->json([
                    'status' => false,
                    'message' => 'الفاتورة غير موجودة'
                ], 404);
            }

            // حذف العناصر المرتبطة أولاً
            $invoice->items()->delete();
            $invoice->delete();

            Log::info('Invoice deleted successfully', ['invoice_id' => $id]);

            return response()->json([
                'status' => true,
                'message' => 'تم حذف الفاتورة بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting invoice: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ في حذف الفاتورة'
            ], 500);
        }
    }

    // دالة لتحديث حالة الفاتورة فقط
    public function updateStatus(Request $request, $id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'يجب تسجيل الدخول أولاً'
                ], 401);
            }

            if (!$user->hasPermission('edit_invoice')) {
                return response()->json([
                    'status' => false,
                    'message' => 'ليس لديك صلاحية لتعديل الفواتير'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:draft,sent,paid,overdue,cancelled'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في التحقق',
                    'errors' => $validator->errors()
                ], 422);
            }

            $invoice = Invoice::find($id);

            if (!$invoice) {
                return response()->json([
                    'status' => false,
                    'message' => 'الفاتورة غير موجودة'
                ], 404);
            }

            $invoice->update(['status' => $request->status]);

            Log::info('Invoice status updated', [
                'invoice_id' => $invoice->id,
                'old_status' => $invoice->getOriginal('status'),
                'new_status' => $invoice->status
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم تحديث حالة الفاتورة بنجاح',
                'data' => $invoice
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating invoice status: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ في تحديث حالة الفاتورة'
            ], 500);
        }
    }
}
