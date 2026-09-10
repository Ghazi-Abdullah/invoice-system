<?php

namespace App\Repository\Admin\Report;

interface ReportInterface
{
    public function getInvoiceReport(array $filters = []): array;
    public function getClientReport(array $filters = []): array;
    public function getRevenueReport(array $filters = []): array;
    public function getOverdueReport(array $filters = []): array;
    public function getAgingReport(array $filters = []): array;
    public function getDashboardStats(): array;
    public function exportReport(string $type, array $filters = []): array;
    public function sendInvoiceReminder(int $invoiceId): array;
    public function markInvoiceAsPaid(int $invoiceId): array;
}