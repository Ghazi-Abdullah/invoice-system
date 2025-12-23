<?php
// app/Repository/User/Invoice/InvoiceInterface.php
namespace App\Repository\User\Invoice;

use App\Models\Invoice;

interface InvoiceInterface
{
    public function index($filters = []);
    public function show(Invoice $invoice);
    public function store($request);
    public function update($request, Invoice $invoice);
    public function destroy(Invoice $invoice);
    public function updateStatus(Invoice $invoice, $status);
    public function getUserStats();
}
