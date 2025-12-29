<?php
// app/Repository/User/Report/ReportInterface.php
namespace App\Repository\User\Report;

interface ReportInterface
{
    public function invoiceReport($filters = []);
    public function clientReport();
    public function getDashboardStats();
}
