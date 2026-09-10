<?php
// app/Mail/SupportTicketCreatedMail.php
namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportTicketCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SupportTicket $ticket) {}

    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject(__('messages.support.email.created_subject', ['ticket' => $this->ticket->ticket_number]))
            ->view('emails.support.created')  // ← الملف الفعلي: created.blade.php
            ->with(['ticket' => $this->ticket]);
    }
}
