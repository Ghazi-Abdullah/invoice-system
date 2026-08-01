<?php

namespace App\Helpers;

use App\Events\InvoiceNotificationUpdated;
use App\Events\InvoiceDueDateAlert;
use App\Models\Invoice;
use App\Constants\Constants;
use Illuminate\Support\Facades\Log;

class InvoiceNotificationHelper
{
    /**
     * ✅ الحصول على العدادات الدقيقة من قاعدة البيانات
     */
    public static function getCounts(): array
    {
        $now = now()->toDateString();

        // فواتير غير مدفوعة: draft, sent, unpaid, pending (أي شيء ليس paid)
        $unpaidCount = Invoice::where('status', '!=', Constants::INVOICE_STATUS_PAID)
            ->where('is_active', true)
            ->count();

        // فواتير متأخرة: status = overdue (يتم تحديثه تلقائياً بالـ Command)
        $overdueCount = Invoice::where('status', Constants::INVOICE_STATUS_OVERDUE)
            ->where('is_active', true)
            ->count();

        // فواتير تستحق قريباً: sent + due_date خلال 7 أيام
        $dueSoonCount = Invoice::where('status', Constants::INVOICE_STATUS_SENT)
            ->whereDate('due_date', '>=', $now)
            ->whereDate('due_date', '<=', now()->addDays(7)->toDateString())
            ->where('is_active', true)
            ->count();

        return [
            'unpaid'    => $unpaidCount,
            'overdue'   => $overdueCount,
            'due_soon'  => $dueSoonCount,
            'total'     => $unpaidCount + $overdueCount + $dueSoonCount,
        ];
    }

    /**
     * ✅ بث العدادات المحدثة للجميع
     */
    public static function broadcastCounts(?string $trigger = null): void
    {
        $counts = self::getCounts();

        Log::info('📢 Broadcasting invoice notification counts', [
            'trigger'   => $trigger,
            'unpaid'    => $counts['unpaid'],
            'overdue'   => $counts['overdue'],
            'due_soon'  => $counts['due_soon'],
            'total'     => $counts['total'],
        ]);

        event(new InvoiceNotificationUpdated(
            $counts['unpaid'],
            $counts['overdue'],
            $counts['due_soon'],
            $trigger
        ));
    }

    /**
     * ✅ بث تنبيهات الفواتير المحددة (للـ toast notifications)
     */
    public static function broadcastDueDateAlerts($dueSoon, $newlyOverdue): void
    {
        if ($dueSoon->isNotEmpty()) {
            event(new InvoiceDueDateAlert('due_soon', self::formatInvoicesForAlert($dueSoon)));
        }

        if ($newlyOverdue->isNotEmpty()) {
            event(new InvoiceDueDateAlert('overdue', self::formatInvoicesForAlert($newlyOverdue)));
        }

        // ✅ بعد بث التنبيهات، بث العدادات المحدثة
        self::broadcastCounts('due_date_check');
    }

    private static function formatInvoicesForAlert($invoices): array
    {
        return $invoices->map(function (Invoice $invoice) {
            return [
                'id'             => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'client_name'    => $invoice->client->name ?? null,
                'client_phone'   => $invoice->client->phone ?? null,
                'due_date'       => $invoice->due_date?->format('Y-m-d'),
            ];
        })->values()->all();
    }
}
