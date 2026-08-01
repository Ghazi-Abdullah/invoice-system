<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceNotificationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $unpaidCount;
    public int $overdueCount;
    public int $dueSoonCount;
    public int $totalCount;
    public ?string $trigger; // سبب التحديث: 'due_date_check', 'invoice_paid', 'invoice_created', etc.

    public function __construct(
        int $unpaidCount,
        int $overdueCount,
        int $dueSoonCount,
        ?string $trigger = null
    ) {
        $this->unpaidCount = $unpaidCount;
        $this->overdueCount = $overdueCount;
        $this->dueSoonCount = $dueSoonCount;
        $this->totalCount = $unpaidCount + $overdueCount + $dueSoonCount;
        $this->trigger = $trigger;
    }

    public function broadcastOn(): array
    {
        return [new Channel('invoice-admin-channel')];
    }

    public function broadcastAs(): string
    {
        return 'invoice-notification-updated';
    }
}
