<?php

namespace App\Helpers;

use App\Events\NotifyInvoiceAdmin;
use App\Models\Invoice;
use App\Constants\Constants;
use Illuminate\Support\Facades\Log; // ✅ إضافة

class InvoiceNotificationHelper
{
    public static function broadcastNotification(): void
    {
        $unpaidCount = Invoice::whereIn('status', [
            Constants::INVOICE_STATUS_UNPAID,
            Constants::INVOICE_STATUS_PENDING,
        ])->count();

        $overdueCount = Invoice::where('status', '!=', Constants::INVOICE_STATUS_PAID)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        Log::info('📢 Broadcasting notification', [
            'unpaid' => $unpaidCount,
            'overdue' => $overdueCount,
        ]);

        event(new NotifyInvoiceAdmin($unpaidCount, $overdueCount));
    }
}
