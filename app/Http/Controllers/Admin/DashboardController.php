<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Constants\Constants;
use App\Helpers\PermissionHelper;
use App\Repository\Admin\Invoice\InvoiceInterface;
use App\Repository\Admin\Client\ClientInterface;
use App\Repository\Admin\User\UserInterface;
use App\Repository\Admin\Report\ReportInterface;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ResponseTrait;

    public $invoice;
    public $client;
    public $user;
    public $report;

    public function __construct(
        InvoiceInterface $invoice,
        ClientInterface $client,
        UserInterface $user,
        ReportInterface $report
    ) {
        $this->invoice = $invoice;
        $this->client = $client;
        $this->user = $user;
        $this->report = $report;
    }

    public function stats(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_DASHBOARD)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $invoiceStats = $this->invoice->getDashboardStats();
        $clientStats = $this->client->index($request);
        $userStats = $this->user->index($request);

        $stats = [
            'invoices' => $invoiceStats['status'] ? $invoiceStats['data'] : null,
            'total_clients' => $clientStats['status'] ? $clientStats['data']->count() : 0,
            'total_users' => $userStats['status'] ? $userStats['data']->count() : 0,
        ];

        return $this->successResponse(
            __('messages.dashboard_stats_fetched'),
            $stats
        );
    }

    public function recentActivity(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_DASHBOARD)) {
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

    public function recentInvoices(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_DASHBOARD)) {
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
        if (!PermissionHelper::checkPermission(Constants::VIEW_DASHBOARD)) {
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

    public function topClients(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_DASHBOARD)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->report->topClients($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.top_clients_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }

    public function monthlyRevenue(Request $request)
    {
        if (!PermissionHelper::checkPermission(Constants::VIEW_DASHBOARD)) {
            return $this->failureResponse(
                __('messages.no_permission'),
                null,
                Constants::RESPONSE_FORBIDDEN
            );
        }

        $data = $this->report->monthlyRevenue($request);

        if ($data['status']) {
            return $this->successResponse(
                __('messages.monthly_revenue_fetched'),
                $data['data']
            );
        }

        return $this->failureResponse($data['message'], $data['data']);
    }
}
