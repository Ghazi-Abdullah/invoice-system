<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use App\Repository\Admin\Invoice\InvoiceInterface;
use App\Http\Requests\Admin\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Admin\Invoice\UpdateInvoiceRequest;
use App\Http\Requests\Admin\Invoice\SendInvoiceRequest;
use App\Http\Requests\Admin\Invoice\MarkAsPaidRequest;
use App\Models\Invoice;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class InvoiceController extends Controller
{
    use ResponseTrait;

    public $invoice;

    public function __construct(InvoiceInterface $invoice)
    {
        $this->invoice = $invoice;
    }

    public function index(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_INVOICES)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->invoice->index($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.invoices_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function show($id)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_INVOICES)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->invoice->show($id);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.invoice_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function store(StoreInvoiceRequest $request)
    {
        if (!PermissionHelper::checkPermission(Constants::CREATE_INVOICE)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->invoice->store($request);

        if ($data['status']) {
            return $this->successResponse( 
                __('messages.invoice_created'),
                $data['data'],
                Constants::RESPONSE_CREATED
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function update(UpdateInvoiceRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::EDIT_INVOICE)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->invoice->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $updateData = $this->invoice->update($request, $data['data']);

        if ($updateData['status']) {
            return $this->successResponse(
                __('messages.invoice_updated'),
                $updateData['data']
            );
        }

        return $this->failureResponse($updateData['message'], $updateData['data']);
    }

    public function destroy($id)
    {
        if (!PermissionHelper::checkPermission(Constants::DELETE_INVOICE)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->invoice->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $deleteData = $this->invoice->destroy($data['data']);

        if ($deleteData['status']) {
            return $this->successResponse(
                __('messages.invoice_deleted'),
                $deleteData['data']
            );
        }

        return $this->failureResponse($deleteData['message'], $deleteData['data']);
    }

    public function send(SendInvoiceRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::SEND_INVOICE)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->invoice->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $sendData = $this->invoice->sendInvoice($data['data']);

        if ($sendData['status']) {
            return $this->successResponse(
                __('messages.invoice_sent'),
                $sendData['data']
            );
        }

        return $this->failureResponse($sendData['message'], $sendData['data']);
    }

    /*public function markAsPaid(MarkAsPaidRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::EDIT_INVOICE)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        // الحصول على بيانات الفاتورة
        $data = $this->invoice->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        // استدعاء دالة markAsPaid من الـ Repository مع المعلمات الصحيحة
        $paidData = $this->invoice->markAsPaid($request, $data['data']);

        if ($paidData['status']) {
            return $this->successResponse(
                __('messages.invoice_marked_paid'),
                $paidData['data']
            );
        }

        return $this->failureResponse($paidData['message'], $paidData['data']);
    }*/

    public function markAsPaid(Request $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::EDIT_INVOICE)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        DB::beginTransaction();

        try {
            // العثور على الفاتورة مباشرة
            $invoice = Invoice::find($id);

            if (!$invoice) {
                return $this->failureResponse(
                    __('messages.invoice_not_found'),
                    null,
                    Constants::RESPONSE_NOT_FOUND
                );
            }

            // التحقق من أن الفاتورة ليست مدفوعة مسبقًا
            if ($invoice->status === 'paid') {
                return $this->failureResponse(
                    __('messages.invoice_already_paid'),
                    null,
                    Constants::RESPONSE_BAD_REQUEST
                );
            }

            // الحصول على تاريخ الدفع من الطلب أو استخدام التاريخ الحالي
            $paymentDate = $request->has('payment_date')
                ? $request->payment_date
                : now()->format('Y-m-d');

            // تحديث الفاتورة
            $invoice->update([
                'status' => 'paid',
                'payment_date' => $paymentDate,
                'paid_at' => now(),
                'updated_at' => now(),
            ]);

            // تسجيل النشاط
           ActivityLog::log(
                'UPDATE',
                'تم تحديث حالة الفاتورة #' . $invoice->invoice_number . ' إلى "تم الدفع"',
                $invoice
            );

            DB::commit();

            return $this->successResponse(
                __('messages.invoice_marked_paid'),
                $invoice->load(['client', 'items'])
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('InvoiceController simpleMarkAsPaid error: ' . $e->getMessage());

            return $this->failureResponse(
                __('messages.operation_failed') . ': ' . $e->getMessage(),
                null,
                Constants::RESPONSE_SERVER_ERROR
            );
        }
    }

    public function duplicate($id)
    {
        if (!PermissionHelper::checkPermission(Constants::CREATE_INVOICE)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->invoice->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $duplicateData = $this->invoice->duplicate($data['data']);

        if ($duplicateData['status']) {
            return $this->successResponse(
                __('messages.invoice_duplicated'),
                $duplicateData['data']
            );
        }

        return $this->failureResponse($duplicateData['message'], $duplicateData['data']);
    }

    public function generatePDF($id)
    {
        if (!PermissionHelper::checkPermission(Constants::DOWNLOAD_INVOICE)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->invoice->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $pdfData = $this->invoice->generatePDF($data['data']);

        if ($pdfData['status']) {
            return $this->successResponse(
                __('messages.pdf_generated'),
                $pdfData['data']
            );
        }

        return $this->failureResponse($pdfData['message'], $pdfData['data']);
    }

    public function downloadPDF($id)
    {
        if (!PermissionHelper::checkPermission(Constants::DOWNLOAD_INVOICE)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->invoice->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $pdfData = $this->invoice->generatePDF($data['data']);

        if (!$pdfData['status']) {
            return $this->failureResponse($pdfData['message'], $pdfData['data']);
        }

        return response()->download(
            storage_path('app/public/' . $pdfData['data']['file_path']),
            $pdfData['data']['file_name']
        );
    }

    public function dashboardStats(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_DASHBOARD)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->invoice->getDashboardStats();

        if ($data['status']) {
            return $this->successResponse(
                __('messages.dashboard_stats_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function recentInvoices(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_INVOICES)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $limit = $request->limit ?? 10;
        $data = $this->invoice->getRecentInvoices($limit);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.recent_invoices_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function overdueInvoices(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_INVOICES)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->invoice->getOverdueInvoices();

        if ($data['status']) {
            return $this->successResponse(
                __('messages.overdue_invoices_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }
}
