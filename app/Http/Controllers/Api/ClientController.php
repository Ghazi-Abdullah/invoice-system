<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class ClientController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            // التحقق من وجود المستخدم
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'يجب تسجيل الدخول أولاً'
                ], 401);
            }

            // التحقق من الصلاحية
            if (!$user->hasPermission('view_clients')) {
                Log::warning('User does not have view_clients permission', ['user_id' => $user->id]);
                return response()->json([
                    'status' => false,
                    'message' => 'ليس لديك صلاحية لعرض العملاء'
                ], 403);
            }

            // إضافة تسجيل للتصحيح
            Log::info('Fetching clients for user', [
                'user_id' => $user->id,
                'admin_group_id' => $user->admin_group_id,
                'is_admin' => $user->admin_group_id == 1
            ]);

            // جلب جميع العملاء بدون أي شروط
            $clients = Client::query();

            // إضافة تسجيل للاستعلام
            Log::info('Client query will fetch: ' . $clients->count() . ' records');

            // البحث
            if ($request->has('search') && $request->search) {
                $clients->where(function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%')
                          ->orWhere('email', 'like', '%' . $request->search . '%')
                          ->orWhere('phone', 'like', '%' . $request->search . '%')
                          ->orWhere('company_name', 'like', '%' . $request->search . '%');
                });
            }

            // حالة النشاط
            if ($request->has('status') && $request->status) {
                $clients->where('status', $request->status);
            }

            // الترتيب
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $clients->orderBy($sortBy, $sortOrder);

            // الصفحة
            $perPage = $request->get('per_page', 15);
            $result = $clients->paginate($perPage);

            // تسجيل نتيجة الاستعلام
            Log::info('Clients fetched successfully', [
                'total' => $result->total(),
                'count' => $result->count(),
                'data_sample' => $result->isNotEmpty() ? $result->first()->toArray() : 'No data'
            ]);

            return response()->json([
                'status' => true,
                'message' => 'تم جلب العملاء بنجاح',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching clients: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ في جلب العملاء: ' . $e->getMessage()
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

            if (!$user->hasPermission('view_clients')) {
                return response()->json([
                    'status' => false,
                    'message' => 'ليس لديك صلاحية لعرض العملاء'
                ], 403);
            }

            $client = Client::with('invoices')->find($id);

            if (!$client) {
                return response()->json([
                    'status' => false,
                    'message' => 'العميل غير موجود'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'تم جلب العميل بنجاح',
                'data' => $client
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching client: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ في جلب العميل'
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

            if (!$user->hasPermission('create_client')) {
                return response()->json([
                    'status' => false,
                    'message' => 'ليس لديك صلاحية لإضافة عملاء'
                ], 403);
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:clients,email',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'company_name' => 'nullable|string|max:255',
                'tax_number' => 'nullable|string|max:50',
                'notes' => 'nullable|string'
            ]);

            $client = Client::create(array_merge($request->all(), [
                'status' => 'active',
                'user_id' => $user->id
            ]));

            Log::info('Client created successfully', ['client_id' => $client->id]);

            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء العميل بنجاح',
                'data' => $client
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating client: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ في إنشاء العميل'
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

            if (!$user->hasPermission('edit_client')) {
                return response()->json([
                    'status' => false,
                    'message' => 'ليس لديك صلاحية لتعديل العملاء'
                ], 403);
            }

            $client = Client::find($id);

            if (!$client) {
                return response()->json([
                    'status' => false,
                    'message' => 'العميل غير موجود'
                ], 404);
            }

            $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:clients,email,' . $id,
                'phone' => 'sometimes|string|max:20',
                'address' => 'sometimes|string',
                'company_name' => 'sometimes|string|max:255',
                'tax_number' => 'sometimes|string|max:50',
                'notes' => 'sometimes|string',
                'status' => 'sometimes|in:active,inactive'
            ]);

            $client->update($request->all());

            Log::info('Client updated successfully', ['client_id' => $client->id]);

            return response()->json([
                'status' => true,
                'message' => 'تم تحديث العميل بنجاح',
                'data' => $client
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating client: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ في تحديث العميل'
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

            if (!$user->hasPermission('delete_client')) {
                return response()->json([
                    'status' => false,
                    'message' => 'ليس لديك صلاحية لحذف العملاء'
                ], 403);
            }

            $client = Client::find($id);

            if (!$client) {
                return response()->json([
                    'status' => false,
                    'message' => 'العميل غير موجود'
                ], 404);
            }

            // التحقق مما إذا كان العميل لديه فواتير
            if ($client->invoices()->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يمكن حذف عميل لديه فواتير مرتبطة'
                ], 400);
            }

            $client->delete();

            Log::info('Client deleted successfully', ['client_id' => $id]);

            return response()->json([
                'status' => true,
                'message' => 'تم حذف العميل بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting client: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ في حذف العميل'
            ], 500);
        }
    }
}
