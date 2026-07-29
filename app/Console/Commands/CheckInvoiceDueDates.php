<?php

namespace App\Console\Commands;

use App\Constants\Constants;
use App\Helpers\InvoiceNotificationHelper;
use App\Models\Invoice;
use Illuminate\Console\Command;

class CheckInvoiceDueDates extends Command
{
    protected $signature = 'invoices:check-due-dates';

    protected $description = 'يفحص الفواتير القريبة من الاستحقاق أو المتأخرة ويبعث تنبيهاً';

    public function handle(): int
    {
        // ✅ 1. تحديث الفواتير المتأخرة تلقائياً (sent + due_date < today)
        $newlyOverdue = Invoice::where('status', Constants::INVOICE_STATUS_SENT)
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        if ($newlyOverdue->isNotEmpty()) {
            Invoice::whereIn('id', $newlyOverdue->pluck('id'))
                ->update(['status' => Constants::INVOICE_STATUS_OVERDUE]);

            $this->info("⚠️ تم تحديث {$newlyOverdue->count()} فاتورة إلى متأخرة.");
        }

        // ✅ 2. جلب الفواتير التي تستحق قريباً
        $dueSoon = Invoice::with('client')
            ->where('status', Constants::INVOICE_STATUS_SENT)
            ->whereDate('due_date', '>=', now()->toDateString())
            ->whereDate('due_date', '<=', now()->addDays(7)->toDateString())
            ->get();

        // ✅ 3. جلب جميع الفواتير المتأخرة (بعد التحديث)
        $overdue = Invoice::with('client')
            ->where('status', Constants::INVOICE_STATUS_OVERDUE)
            ->get();

        // ✅ 4. بث التنبيهات + العدادات
        if ($dueSoon->isNotEmpty() || $newlyOverdue->isNotEmpty()) {
            InvoiceNotificationHelper::broadcastDueDateAlerts($dueSoon, $newlyOverdue);
        } else {
            // حتى لو لا يوجد جديد، بث العدادات للتأكد من التزامن
            InvoiceNotificationHelper::broadcastCounts('daily_check');
        }

        $this->info(
            "✅ تم الفحص: {$dueSoon->count()} تستحق قريباً، {$overdue->count()} متأخرة."
        );

        return self::SUCCESS;
    }
}
