<?php

namespace App\Repository\Admin\Invoice;

interface InvoiceInterface
{
    public function index($request);
    public function show($id);
    public function store($request);
    public function update($request, $invoice);
    public function destroy($invoice);
    public function sendInvoice($invoice);
    public function markAsPaid($request, $invoice);
    public function duplicate($invoice);
    public function generatePDF($invoice);
    public function getDashboardStats();
    public function getRecentInvoices($limit = 10);
    public function getOverdueInvoices();
}
