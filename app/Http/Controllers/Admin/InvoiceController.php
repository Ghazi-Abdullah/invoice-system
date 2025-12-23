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
use Illuminate\Http\Request;

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
            return $this->failureResponse(__('messages.no_permission'), null, 403);
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
            return $this->failureResponse(__('messages.no_permission'), null, 403);
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
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->invoice->store($request);

        if ($data['status']) {
            return $this->successResponse($data['message'], $data['data'], 201);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function update(UpdateInvoiceRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::EDIT_INVOICE)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->invoice->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $updateData = $this->invoice->update($request, $data['data']);

        if ($updateData['status']) {
            return $this->successResponse($updateData['message'], $updateData['data']);
        }

        return $this->failureResponse($updateData['message'], $updateData['data']);
    }

    public function destroy($id)
    {
        if (!PermissionHelper::checkPermission(Constants::DELETE_INVOICE)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->invoice->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $deleteData = $this->invoice->destroy($data['data']);

        if ($deleteData['status']) {
            return $this->successResponse($deleteData['message'], $deleteData['data']);
        }

        return $this->failureResponse($deleteData['message'], $deleteData['data']);
    }

    public function send(SendInvoiceRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::SEND_INVOICE)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->invoice->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $sendData = $this->invoice->sendInvoice($data['data']);

        if ($sendData['status']) {
            return $this->successResponse($sendData['message'], $sendData['data']);
        }

        return $this->failureResponse($sendData['message'], $sendData['data']);
    }

    public function markAsPaid(MarkAsPaidRequest $request, $id)
    {
        if (!PermissionHelper::checkPermission(Constants::EDIT_INVOICE)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->invoice->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $paidData = $this->invoice->markAsPaid($data['data']);

        if ($paidData['status']) {
            return $this->successResponse($paidData['message'], $paidData['data']);
        }

        return $this->failureResponse($paidData['message'], $paidData['data']);
    }

    public function duplicate($id)
    {
        if (!PermissionHelper::checkPermission(Constants::CREATE_INVOICE)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->invoice->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $duplicateData = $this->invoice->duplicate($data['data']);

        if ($duplicateData['status']) {
            return $this->successResponse($duplicateData['message'], $duplicateData['data']);
        }

        return $this->failureResponse($duplicateData['message'], $duplicateData['data']);
    }

    public function generatePDF($id)
    {
        if (!PermissionHelper::checkPermission(Constants::DOWNLOAD_INVOICE)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->invoice->show($id);

        if (!$data['status']) {
            return $this->failureResponse($data['message'], $data['data']);
        }

        $pdfData = $this->invoice->generatePDF($data['data']);

        if ($pdfData['status']) {
            return $this->successResponse($pdfData['message'], $pdfData['data']);
        }

        return $this->failureResponse($pdfData['message'], $pdfData['data']);
    }

    public function downloadPDF($id)
    {
        if (!PermissionHelper::checkPermission(Constants::DOWNLOAD_INVOICE)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
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
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->invoice->getDashboardStats();

        if ($data['status']) {
            return $this->successResponse($data['message'], $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function recentInvoices(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_INVOICES)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $limit = $request->limit ?? 10;
        $data = $this->invoice->getRecentInvoices($limit);

        if ($data['status']) {
            return $this->successResponse($data['message'], $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function overdueInvoices(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_INVOICES)) {
            return $this->failureResponse(__('messages.no_permission'), null, 403);
        }

        $data = $this->invoice->getOverdueInvoices();

        if ($data['status']) {
            return $this->successResponse($data['message'], $data['data']);
        }

        return $this->failureResponse($data['message'], $data['data']);
    }
}
