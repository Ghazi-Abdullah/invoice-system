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

    public function __construct(
        public int $unpaid,
        public int $overdue,
        public int $dueSoon,
        public int $openTickets,
        public ?string $trigger = null
    ) {}

    public function broadcastOn(): array
    {
        // ← توافق مع Frontend اللي يستمع على invoice-admin-channel
        return [new Channel('invoice-admin-channel')];
    }

    public function broadcastAs(): string
    {
        return 'invoice-notification-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'unpaidCount'   => $this->unpaid,
            'overdueCount'  => $this->overdue,
            'dueSoonCount'  => $this->dueSoon,
            'openTicketsCount' => $this->openTickets,
            'trigger'       => $this->trigger,
        ];
    }
}
