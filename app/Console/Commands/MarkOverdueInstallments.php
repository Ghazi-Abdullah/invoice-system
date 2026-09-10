<?php

namespace App\Console\Commands;

use App\Models\Installment;
use Illuminate\Console\Command;

class MarkOverdueInstallments extends Command
{
    protected $signature = 'installments:mark-overdue';

    protected $description = 'Mark installments as  overdue';

    public function handle()
    {
        $count = Installment::where('status', 'pending')
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        $this->info("Updated {$count} installments to overdue status.");

        return self::SUCCESS;
    }
}
