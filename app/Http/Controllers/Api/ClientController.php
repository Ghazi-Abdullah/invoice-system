<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        try {
            $userId = $request->user()->id;

            Log::info('👥 جلب العملاء للمستخدم:', [
                'user_id' => $userId,
                'email' => $request->user()->email
            ]);

            // استعلام لجميع العملاء الخاصة بالمستخدم الحالي بالإضافة إلى العملاء الافتراضيين
            $query = Client::where(function($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere('user_id', 1); // العملاء الافتراضيين
            });

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

    public function show(Client $client)
    {
        try {
            // التحقق من أن العميل يخص المستخدم
            if (request()->user()->id !== $client->user_id) {
                Log::warning('⚠️ محاولة وصول غير مصرح للعميل:', [
                    'client_user_id' => $client->user_id,
                    'current_user_id' => request()->user()->id
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول لهذا العميل'
                ], 403);
            }

            $client->load(['invoices']);

            return response()->json([
                'success' => true,
                'data' => $client
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في جلب تفاصيل العميل:', [
                'client_id' => $client->id ?? 'غير معروف',
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
            // التحقق من أن العميل يخص المستخدم
            if ($request->user()->id !== $client->user_id) {
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
                'name' => $client->name
            ]);

            return response()->json([
                'success' => true,
                'data' => $client,
                'message' => 'تم تحديث العميل بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في تحديث العميل:', [
                'client_id' => $client->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في تحديث العميل: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Client $client)
    {
        try {
            // التحقق من أن العميل يخص المستخدم
            if (request()->user()->id !== $client->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بحذف هذا العميل'
                ], 403);
            }

            $client->delete();

            Log::info('🗑️ تم حذف العميل:', [
                'id' => $client->id,
                'name' => $client->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف العميل بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطأ في حذف العميل:', [
                'client_id' => $client->id,
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

            // ربط العملاء التي ليس لها مستخدم أو لها user_id = 1
            $updated = DB::transaction(function () use ($userId) {
                $nullClients = Client::whereNull('user_id')
                    ->update(['user_id' => $userId]);

                $demoClients = Client::where('user_id', 1)
                    ->where('id', '>', 0) // تأكد من وجود سجلات
                    ->update(['user_id' => $userId]);

                return $nullClients + $demoClients;
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
}
