<?php
// app/Mail/InvoiceOverdueMail.php
namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceOverdueMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject(__('messages.invoices.email.overdue_subject', ['number' => $this->invoice->invoice_number]))
            ->view('emails.invoices.overdue') // ← الملف الفعلي: overdue.blade.php
            ->with(['invoice' => $this->invoice]);
    }
}
