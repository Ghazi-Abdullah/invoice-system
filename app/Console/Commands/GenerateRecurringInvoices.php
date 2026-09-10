<?php

namespace App\Console\Commands;

use App\Models\RecurringInvoiceTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'invoices:generate-recurring';

    protected $description = 'ينشئ الفواتير المستحقة من قوالب الفواتير المتكررة النشطة';

    public function handle(): int
    {
        $dueTemplates = RecurringInvoiceTemplate::due()->get();

        if ($dueTemplates->isEmpty()) {
            $this->info('لا توجد قوالب فواتير متكررة مستحقة اليوم.');
            return self::SUCCESS;
        }

        $generated = 0;
        $failed    = 0;

        foreach ($dueTemplates as $template) {
            try {
                $invoice = $template->generateInvoice();
                $generated++;
                $this->info("✅ تم إنشاء الفاتورة {$invoice->invoice_number} من القالب #{$template->id}");
            } catch (\Throwable $e) {
                $failed++;
                Log::error("فشل توليد فاتورة متكررة من القالب #{$template->id}: {$e->getMessage()}");
                $this->error("❌ فشل القالب #{$template->id}: {$e->getMessage()}");
            }
        }

        $this->info("✅ الملخص: {$generated} فاتورة أُنشئت، {$failed} فشلت.");

        return self::SUCCESS;
    }
}