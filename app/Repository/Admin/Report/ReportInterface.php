<?php
// app/Repository/Admin/Report/ReportInterface.php
namespace App\Repository\Admin\Report;

interface ReportInterface
{
    public function invoiceReport($filters = []);
    public function clientReport($filters = []);
    public function revenueReport($filters = []);
    public function overdueReport($filters = []);
    public function exportReport($type, $filters = []);
}
