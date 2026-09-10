<?php

namespace App\Console\Commands;

use App\Constants\Constants;
use App\Helpers\InvoiceNotificationHelper;
use App\Mail\InvoiceDueSoonMail;
use App\Mail\InvoiceOverdueMail;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckInvoiceDueDates extends Command
{
    protected $signature = 'invoices:check-due-dates';

    protected $description = 'يفحص الفواتير القريبة من الاستحقاق أو المتأخرة، ويبعث تنبيهاً داخلياً وتذكير بريد إلكتروني للعميل';

    public function handle(): int
    {
        // ✅ 1. تحديث الفواتير المتأخرة تلقائياً (sent + due_date < today)
        $newlyOverdue = Invoice::with('client')
            ->where('status', Constants::INVOICE_STATUS_SENT)
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        if ($newlyOverdue->isNotEmpty()) {
            Invoice::whereIn('id', $newlyOverdue->pluck('id'))
                ->update(['status' => Constants::INVOICE_STATUS_OVERDUE]);

            $this->info("⚠️ تم تحديث {$newlyOverdue->count()} فاتورة إلى متأخرة.");

            // ✅ إشعار العميل بالتأخير — يُرسل مرة واحدة فقط، لحظة التحول لمتأخرة
            $this->sendReminders($newlyOverdue, InvoiceOverdueMail::class);
        }

        // ✅ 2. جلب الفواتير المستحقة قريباً واللي ما أُرسل لها تذكير من قبل
        //    (whereNull يمنع إرسال نفس التذكير يومياً طوال نافذة الـ 7 أيام)
        $dueSoon = Invoice::with('client')
            ->where('status', Constants::INVOICE_STATUS_SENT)
            ->whereNull('due_reminder_sent_at')
            ->whereDate('due_date', '>=', now()->toDateString())
            ->whereDate('due_date', '<=', now()->addDays(7)->toDateString())
            ->get();

        if ($dueSoon->isNotEmpty()) {
            // ✅ تذكير العميل، ثم تعليم الفواتير كمُرسلة لها تذكير
            $this->sendReminders($dueSoon, InvoiceDueSoonMail::class);

            Invoice::whereIn('id', $dueSoon->pluck('id'))
                ->update(['due_reminder_sent_at' => now()]);
        }

        // ✅ 3. جلب جميع الفواتير المتأخرة (بعد التحديث) — لأغراض العدّ والبث فقط
        $overdue = Invoice::with('client')
            ->where('status', Constants::INVOICE_STATUS_OVERDUE)
            ->get();

        // ✅ 4. بث التنبيهات + العدادات (للوحة تحكم الأدمن، كما كان سابقاً)
        if ($dueSoon->isNotEmpty() || $newlyOverdue->isNotEmpty()) {
            InvoiceNotificationHelper::broadcastDueDateAlerts($dueSoon, $newlyOverdue);
        } else {
            InvoiceNotificationHelper::broadcastCounts('daily_check');
        }

        $this->info(
            "✅ تم الفحص: {$dueSoon->count()} تستحق قريباً، {$overdue->count()} متأخرة."
        );

        return self::SUCCESS;
    }

    /**
     * إرسال إيميل تذكير/تنبيه لكل فاتورة في المجموعة، مع تسجيل أي فشل
     * بدلاً من إيقاف تنفيذ باقي الأمر.
     */
    protected function sendReminders($invoices, string $mailClass): void
    {
        foreach ($invoices as $invoice) {
            if (empty($invoice->client?->email)) {
                continue;
            }

            try {
                Mail::to($invoice->client->email)->queue(new $mailClass($invoice));
            } catch (\Throwable $e) {
                Log::error("فشل إرسال تذكير الفاتورة #{$invoice->invoice_number}: {$e->getMessage()}");
            }
        }
    }
}
