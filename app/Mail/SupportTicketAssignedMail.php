<?php
// app/Mail/SupportTicketAssignedMail.php
namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SupportTicketAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public SupportTicket $ticket) {}

    public function build()
    {
        return $this->subject('تم تعيين تذكرة لك: ' . $this->ticket->ticket_number)
            ->view('emails.support.assigned')
            ->with(['ticket' => $this->ticket]);
    }
}
