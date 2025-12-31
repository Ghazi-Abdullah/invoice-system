<?php

namespace App\Repository\Admin\Client;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\ActivityLog;
use App\Constants\Constants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientRepository implements ClientInterface
{
    public function index($request)
    {
        try {
            $query = Client::with(['user', 'invoices'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('is_active') && $request->is_active !== '') {
                $query->where('is_active', $request->is_active);
            }

            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%")
                        ->orWhere('company_name', 'like', "%{$request->search}%")
                        ->orWhere('phone', 'like', "%{$request->search}%");
                });
            }

            // Get paginated or all results
            if ($request->has('per_page')) {
                $perPage = (int) $request->per_page;
                $perPage = min($perPage, Constants::MAX_PER_PAGE);
                $perPage = max($perPage, Constants::MIN_PER_PAGE);

                $clients = $query->paginate($perPage);
            } else {
                $clients = $query->get();
            }

            // إضافة الإحصائيات لكل عميل
            foreach ($clients as $client) {
                $client->invoices_count = $client->invoices()->count();
                $client->total_invoiced = $client->invoices()->sum('total');
                $client->total_paid = $client->invoices()->where('status', Constants::INVOICE_STATUS_PAID)->sum('total');
                $client->total_due = $client->total_invoiced - $client->total_paid;
            }

            return [
                'status' => true,
                'message' => __('messages.clients_fetched'),
                'data' => $clients
            ];
        } catch (\Exception $e) {
            Log::error('ClientRepository index error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'status' => false,
                'message' => __('messages.operation_failed') . ': ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function show($id)
    {
        try {
            $client = Client::with(['user', 'invoices'])->find($id);

            if (!$client) {
                return [
                    'status' => false,
                    'message' => __('messages.client_not_found'),
                    'data' => null
                ];
            }

            // إضافة الإحصائيات
            $client->invoices_count = $client->invoices()->count();
            $client->total_invoiced = $client->invoices()->sum('total');
            $client->total_paid = $client->invoices()->where('status', Constants::INVOICE_STATUS_PAID)->sum('total');
            $client->total_due = $client->total_invoiced - $client->total_paid;

            return [
                'status' => true,
                'message' => __('messages.client_fetched'),
                'data' => $client
            ];
        } catch (\Exception $e) {
            Log::error('ClientRepository show error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => __('messages.operation_failed') . ': ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function store($request)
    {
        DB::beginTransaction();

        try {
            // التحقق من البريد الإلكتروني الفريد
            $existingClient = Client::where('email', $request->email)->first();
            if ($existingClient) {
                return [
                    'status' => false,
                    'message' => __('messages.email_already_registered'),
                    'data' => null
                ];
            }

            // إنشاء العميل
            $client = Client::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone ?? null,
                'address' => $request->address ?? null,
                'company_name' => $request->company_name ?? null,
                'tax_number' => $request->tax_number ?? null,
                'payment_terms' => $request->payment_terms ?? Constants::PAYMENT_TERM_NET_30,
                'currency' => $request->currency ?? Constants::CURRENCY_SAR,
                'notes' => $request->notes ?? null,
                'is_active' => $request->is_active ?? true,
                'created_by' => auth()->id() ?? 1
            ]);

            // تسجيل النشاط
            ActivityLog::log(
                'CREATE',
                __('messages.client_created') . ': ' . $client->name,
                $client
            );

            DB::commit();

            return [
                'status' => true,
                'message' => __('messages.client_created'),
                'data' => $client->load(['user'])
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ClientRepository store error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => __('messages.operation_failed') . ': ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function update($request, $client)
    {
        DB::beginTransaction();

        try {
            $oldValues = $client->toArray();

            // التحقق من البريد الإلكتروني الفريد (استثناء العميل الحالي)
            if ($request->has('email') && $request->email !== $client->email) {
                $existingClient = Client::where('email', $request->email)
                    ->where('id', '!=', $client->id)
                    ->first();

                if ($existingClient) {
                    return [
                        'status' => false,
                        'message' => __('messages.email_already_registered'),
                        'data' => null
                    ];
                }
            }

            // الحقول المسموح بتحديثها فقط
            $allowedFields = [
                'name',
                'email',
                'phone',
                'address',
                'company_name',
                'tax_number',
                'payment_terms',
                'currency',
                'notes',
                'is_active'
            ];

            // إنشاء array للبيانات المسموح بها فقط
            $updateData = [];
            foreach ($allowedFields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->$field;
                }
            }

            // تحويل is_active إلى boolean إذا كان موجوداً
            if (isset($updateData['is_active'])) {
                $updateData['is_active'] = (bool)$updateData['is_active'];
            }

            // تحديث البيانات
            $client->update($updateData);

            // تسجيل النشاط
            ActivityLog::log(
                'UPDATE',
                __('messages.client_updated') . ': ' . $client->name,
                $client,
                $oldValues,
                $client->fresh()->toArray()
            );

            DB::commit();

            // إعادة تحميل العميل مع الإحصائيات المحسوبة (فقط للعرض)
            $client->load(['user', 'invoices']);
            $client->invoices_count = $client->invoices()->count();
            $client->total_invoiced = $client->invoices()->sum('total');
            $client->total_paid = $client->invoices()->where('status', Constants::INVOICE_STATUS_PAID)->sum('total');
            $client->total_due = $client->total_invoiced - $client->total_paid;

            return [
                'status' => true,
                'message' => __('messages.client_updated'),
                'data' => $client
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ClientRepository update error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'status' => false,
                'message' => __('messages.operation_failed') . ': ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function destroy($client)
    {
        DB::beginTransaction();

        try {
            $clientName = $client->name;
            $clientId = $client->id;

            // التحقق إذا كان العميل لديه فواتير مرتبطة
            if ($client->invoices()->count() > 0) {
                return [
                    'status' => false,
                    'message' => __('messages.client_has_invoices'),
                    'data' => null
                ];
            }

            // تسجيل النشاط قبل الحذف
            ActivityLog::log(
                'DELETE',
                __('messages.client_deleted') . ': ' . $clientName,
                $client
            );

            // حذف العميل
            $client->delete();

            DB::commit();

            return [
                'status' => true,
                'message' => __('messages.client_deleted'),
                'data' => ['id' => $clientId]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ClientRepository destroy error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => __('messages.operation_failed') . ': ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getClientStats($client)
    {
        try {
            $totalInvoices = $client->invoices()->count();
            $totalAmount = $client->invoices()->sum('total');
            $paidAmount = $client->invoices()
                ->where('status', Constants::INVOICE_STATUS_PAID)
                ->sum('total');
            $pendingAmount = $client->invoices()
                ->where('status', Constants::INVOICE_STATUS_SENT)
                ->sum('total');
            $overdueAmount = $client->invoices()
                ->where('status', Constants::INVOICE_STATUS_OVERDUE)
                ->sum('total');

            $stats = [
                'total_invoices' => $totalInvoices,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'pending_amount' => $pendingAmount,
                'overdue_amount' => $overdueAmount,
                'currency' => $client->currency ?? Constants::CURRENCY_SAR
            ];

            return [
                'status' => true,
                'message' => __('messages.client_stats_fetched'),
                'data' => $stats
            ];
        } catch (\Exception $e) {
            Log::error('ClientRepository getClientStats error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => __('messages.operation_failed') . ': ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function searchClients($request)
    {
        try {
            $search = $request->search ?? '';

            $clients = Client::where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('company_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->limit(10)
                ->get(['id', 'name', 'email', 'phone', 'company_name', 'is_active']);

            return [
                'status' => true,
                'message' => __('messages.client_search_fetched'),
                'data' => $clients
            ];
        } catch (\Exception $e) {
            Log::error('ClientRepository searchClients error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => __('messages.operation_failed') . ': ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function getClientInvoices($client)
    {
        try {
            $invoices = $client->invoices()
                ->orderBy('created_at', 'desc')
                ->paginate(Constants::DEFAULT_PER_PAGE);

            return [
                'status' => true,
                'message' => __('messages.client_invoices_fetched'),
                'data' => $invoices
            ];
        } catch (\Exception $e) {
            Log::error('ClientRepository getClientInvoices error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => __('messages.operation_failed') . ': ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
