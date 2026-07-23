<?php

namespace App\Observers;

use App\Helpers\InvoiceNotificationHelper;
use App\Models\Invoice;
use App\Constants\Constants;

class InvoiceObserver
{
    public function updated(Invoice $invoice): void
    {
        if ($invoice->wasChanged('status')) {
            InvoiceNotificationHelper::broadcastCounts('invoice_status_changed');
        }
    }

    public function created(Invoice $invoice): void
    {
        if ($invoice->status !== Constants::INVOICE_STATUS_PAID) {
            InvoiceNotificationHelper::broadcastCounts('invoice_created');
        }
    }

    public function deleted(Invoice $invoice): void
    {
        InvoiceNotificationHelper::broadcastCounts('invoice_deleted');
    }
}
