<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class NotifyInvoiceAdmin implements ShouldBroadcastNow
{
    use SerializesModels;

    public int $unpaidCount;
    public int $overdueCount;

    public function __construct(int $unpaidCount, int $overdueCount)
    {
        $this->unpaidCount = $unpaidCount;
        $this->overdueCount = $overdueCount;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('invoice-admin-channel');
    }

    public function broadcastAs(): string
    {
        return 'notify-invoice-admin';
    }
}
