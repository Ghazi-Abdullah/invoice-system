<?php
// app/Repositories/Admin/InvoiceSummary/InvoiceSummaryInterface.php
namespace App\Repositories\Admin\InvoiceSummary;

interface InvoiceSummaryInterface
{
    public function index($request);
    public function getClientReport($request, $userId);
    public function getOverdueReport($request, $userId);
    public function getRevenueReport($request, $userId);
}
