<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function __construct()
    {
        // تعليق مؤقت للصلاحيات حتى يتم تعيين الأدوار
        // $this->middleware('permission:view_clients')->only(['index', 'show']);
        // $this->middleware('permission:create_clients')->only(['store']);
        // $this->middleware('permission:edit_clients')->only(['update']);
        // $this->middleware('permission:delete_clients')->only(['destroy']);
    }

    public function index(Request $request)
    {
        try {
            $userId = $request->user()->id;

            Log::info('👥 جلب العملاء للمستخدم:', [
                'user_id' => $userId,
                'email' => $request->user()->email
            ]);

            // استعلام لجميع العملاء الخاصة بالمستخدم الحالي
            $query = Client::where('user_id', $userId);

            // تطبيق الفلاتر
            if ($request->has('search') && $request->search !== '') {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%')
                      ->orWhere('phone', 'like', '%' . $request->search . '%');
                });
            }

            $clients = $query->latest()->paginate(20);

            Log::info('📊 نتيجة جلب العملاء:', [
                'total_clients' => $clients->total(),
                'current_count' => $clients->count(),
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'data' => $clients
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في جلب العملاء: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id ?? 'غير معروف'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب العملاء: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:clients,email',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'company_name' => 'nullable|string|max:255',
                'tax_number' => 'nullable|string|max:50',
            ]);

            $client = Client::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'company_name' => $request->company_name,
                'tax_number' => $request->tax_number,
                'user_id' => $request->user()->id
            ]);

            Log::info('✅ تم إنشاء عميل جديد:', [
                'id' => $client->id,
                'name' => $client->name,
                'user_id' => $client->user_id
            ]);

            return response()->json([
                'success' => true,
                'data' => $client,
                'message' => 'تم إنشاء العميل بنجاح'
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في إنشاء العميل:', [
                'user_id' => $request->user()->id ?? 'غير معروف',
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في إنشاء العميل: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, Client $client)
    {
        try {
            $userId = $request->user()->id;

            // التحقق من أن العميل يخص المستخدم
            if ($client->user_id !== $userId) {
                Log::warning('⚠️ محاولة وصول غير مصرح للعميل:', [
                    'client_user_id' => $client->user_id,
                    'current_user_id' => $userId
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول لهذا العميل'
                ], 403);
            }

            $client->load(['invoices', 'invoices.items']);

            Log::info('📄 عرض تفاصيل العميل:', [
                'client_id' => $client->id,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'data' => $client
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في جلب تفاصيل العميل:', [
                'client_id' => $client->id ?? 'غير معروف',
                'user_id' => $request->user()->id ?? 'غير معروف',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب تفاصيل العميل: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Client $client)
    {
        try {
            $userId = $request->user()->id;

            // التحقق من أن العميل يخص المستخدم
            if ($client->user_id !== $userId) {
                Log::warning('⚠️ محاولة تعديل غير مصرح للعميل:', [
                    'client_user_id' => $client->user_id,
                    'current_user_id' => $userId,
                    'action' => 'update'
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بتحديث هذا العميل'
                ], 403);
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:clients,email,' . $client->id,
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'company_name' => 'nullable|string|max:255',
                'tax_number' => 'nullable|string|max:50',
            ]);

            $client->update($request->all());

            Log::info('✏️ تم تحديث العميل:', [
                'id' => $client->id,
                'name' => $client->name,
                'user_id' => $client->user_id
            ]);

            return response()->json([
                'success' => true,
                'data' => $client,
                'message' => 'تم تحديث العميل بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في تحديث العميل:', [
                'client_id' => $client->id,
                'user_id' => $request->user()->id ?? 'غير معروف',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في تحديث العميل: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, Client $client)
    {
        try {
            $userId = $request->user()->id;

            // التحقق من أن العميل يخص المستخدم
            if ($client->user_id !== $userId) {
                Log::warning('⚠️ محاولة حذف غير مصرح للعميل:', [
                    'client_user_id' => $client->user_id,
                    'current_user_id' => $userId,
                    'action' => 'delete'
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بحذف هذا العميل'
                ], 403);
            }

            // التحقق من عدم وجود فواتير مرتبطة بالعميل
            if ($client->invoices()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف العميل لأن لديه فواتير مرتبطة'
                ], 400);
            }

            $client->delete();

            Log::info('🗑️ تم حذف العميل:', [
                'id' => $client->id,
                'name' => $client->name,
                'user_id' => $client->user_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف العميل بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في حذف العميل:', [
                'client_id' => $client->id,
                'user_id' => $request->user()->id ?? 'غير معروف',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في حذف العميل: ' . $e->getMessage()
            ], 500);
        }
    }

    // دالة مساعدة: ربط جميع العملاء القدامى بالمستخدم الحالي (للتطوير فقط)
    public function linkAllClients(Request $request)
    {
        try {
            $userId = $request->user()->id;

            Log::info('🔄 محاولة ربط جميع العملاء للمستخدم:', ['user_id' => $userId]);

            // ربط العملاء التي ليس لها مستخدم
            $updated = DB::transaction(function () use ($userId) {
                return Client::whereNull('user_id')
                    ->update(['user_id' => $userId]);
            });

            Log::info('✅ تم ربط العملاء:', ['count' => $updated]);

            return response()->json([
                'success' => true,
                'message' => 'تم ربط ' . $updated . ' عميل بالمستخدم الحالي',
                'linked_count' => $updated
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في ربط العملاء:', [
                'user_id' => $request->user()->id ?? 'غير معروف',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في ربط العملاء: ' . $e->getMessage()
            ], 500);
        }
    }

    // دالة جديدة لجلب قائمة العملاء المبسطة للفلاتر والتحديدات
    public function getSimpleList(Request $request)
    {
        try {
            $userId = $request->user()->id;

            // استعلام لجميع العملاء الخاصة بالمستخدم الحالي
            $clients = Client::where('user_id', $userId)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $clients,
                'message' => 'تم جلب قائمة العملاء بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في جلب قائمة العملاء المبسطة: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? 'غير معروف'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب قائمة العملاء: ' . $e->getMessage()
            ], 500);
        }
    }
}
