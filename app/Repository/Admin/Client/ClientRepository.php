<?php

namespace App\Repository\Admin\Client;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\ActivityLog;
use App\Constants\Constants;
use Illuminate\Support\Facades\DB;

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
                $query->where(function($q) use ($request) {
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
                'message' => 'Clients retrieved successfully',
                'data' => $clients
            ];

        } catch (\Exception $e) {
            \Log::error('ClientRepository index error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return [
                'status' => false,
                'message' => 'Failed to retrieve clients: ' . $e->getMessage(),
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
                    'message' => 'Client not found',
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
                'message' => 'Client retrieved successfully',
                'data' => $client
            ];

        } catch (\Exception $e) {
            \Log::error('ClientRepository show error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to retrieve client: ' . $e->getMessage(),
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
                    'message' => 'Email already exists',
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
                'Created client: ' . $client->name,
                $client
            );

            DB::commit();

            return [
                'status' => true,
                'message' => 'Client created successfully',
                'data' => $client->load(['user'])
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('ClientRepository store error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to create client: ' . $e->getMessage(),
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
                        'message' => 'Email already exists',
                        'data' => null
                    ];
                }
            }

            // تحديث بيانات العميل
            $updateData = [
                'name' => $request->name ?? $client->name,
                'email' => $request->email ?? $client->email,
                'phone' => $request->phone ?? $client->phone,
                'address' => $request->address ?? $client->address,
                'company_name' => $request->company_name ?? $client->company_name,
                'tax_number' => $request->tax_number ?? $client->tax_number,
                'payment_terms' => $request->payment_terms ?? $client->payment_terms,
                'currency' => $request->currency ?? $client->currency,
                'notes' => $request->notes ?? $client->notes,
                'is_active' => $request->has('is_active') ? (bool)$request->is_active : $client->is_active
            ];

            $client->update($updateData);

            // تسجيل النشاط
            ActivityLog::log(
                'UPDATE',
                'Updated client: ' . $client->name,
                $client,
                $oldValues,
                $client->fresh()->toArray()
            );

            DB::commit();

            return [
                'status' => true,
                'message' => 'Client updated successfully',
                'data' => $client->load(['user'])
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('ClientRepository update error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to update client: ' . $e->getMessage(),
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
                    'message' => 'Cannot delete client with existing invoices',
                    'data' => null
                ];
            }

            // تسجيل النشاط قبل الحذف
            ActivityLog::log(
                'DELETE',
                'Deleted client: ' . $clientName,
                $client
            );

            // حذف العميل
            $client->delete();

            DB::commit();

            return [
                'status' => true,
                'message' => 'Client deleted successfully',
                'data' => ['id' => $clientId]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('ClientRepository destroy error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to delete client: ' . $e->getMessage(),
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
                'message' => 'Client stats retrieved successfully',
                'data' => $stats
            ];

        } catch (\Exception $e) {
            \Log::error('ClientRepository getClientStats error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to retrieve client stats: ' . $e->getMessage(),
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
                'message' => 'Clients search completed',
                'data' => $clients
            ];

        } catch (\Exception $e) {
            \Log::error('ClientRepository searchClients error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to search clients: ' . $e->getMessage(),
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
                'message' => 'Client invoices retrieved successfully',
                'data' => $invoices
            ];

        } catch (\Exception $e) {
            \Log::error('ClientRepository getClientInvoices error: ' . $e->getMessage());

            return [
                'status' => false,
                'message' => 'Failed to retrieve client invoices: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
