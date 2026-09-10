<?php
// app/Mail/NewSupportTicketMail.php — إشعار الأدمن/الفريق عند تذكرة جديدة غير معيّنة
namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewSupportTicketMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public SupportTicket $ticket) {}

    public function build()
    {
        return $this->subject('تذكرة دعم جديدة: ' . $this->ticket->ticket_number)
            ->view('emails.support.new-ticket')
            ->with(['ticket' => $this->ticket]);
    }
}
