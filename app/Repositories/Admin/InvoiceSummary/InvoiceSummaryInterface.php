<?php
namespace App\Repositories\Admin\InvoiceSummary;

interface InvoiceSummaryInterface
{
    public function index($request);
    public function getClientReport($request);
    public function getOverdueReport($request);
    public function getRevenueReport($request);
}
