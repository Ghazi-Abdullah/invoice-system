<?php
// app/Mail/SupportTicketReplyMail.php
namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SupportTicketReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public string $replyMessage,
        public bool $fromAdmin,
    ) {}

    public function build()
    {
        return $this->subject('رد جديد على تذكرتك: ' . $this->ticket->ticket_number)
            ->view('emails.support.reply')
            ->with([
                'ticket'      => $this->ticket,
                'replyMessage' => $this->replyMessage,
                'fromAdmin'   => $this->fromAdmin,
            ]);
    }
}
