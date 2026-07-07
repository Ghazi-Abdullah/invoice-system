<?php

namespace App\Console\Commands;

use App\Models\Installment;
use Illuminate\Console\Command;

class MarkOverdueInstallments extends Command
{
    protected $signature = 'installments:mark-overdue';

    protected $description = 'تحديث حالة الأقساط اللي فات موعد استحقاقها ولسا ما انسددت إلى overdue';

    public function handle()
    {
        $count = Installment::where('status', 'pending')
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        $this->info("تم تحديث {$count} قسط إلى حالة متأخر.");

        return self::SUCCESS;
    }
}
