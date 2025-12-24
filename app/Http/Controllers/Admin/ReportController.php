<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use App\Repository\Admin\Report\ReportInterface;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ResponseTrait;

    public $report;

    public function __construct(ReportInterface $report)
    {
        $this->report = $report;
    }

    public function invoiceReport(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_REPORTS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->report->invoiceReport($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.invoice_report_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function clientReport(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_REPORTS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->report->clientReport($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.client_report_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function paymentReport(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_REPORTS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->report->paymentReport($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.payment_report_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function taxReport(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_REPORTS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->report->taxReport($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.tax_report_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function exportInvoiceReport(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::EXPORT_REPORTS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->report->exportInvoiceReport($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.report_exported'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function exportClientReport(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::EXPORT_REPORTS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->report->exportClientReport($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.report_exported'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function exportPaymentReport(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::EXPORT_REPORTS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->report->exportPaymentReport($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.report_exported'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function recentActivity(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_REPORTS)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->report->recentActivity($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.recent_activity_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }
}
