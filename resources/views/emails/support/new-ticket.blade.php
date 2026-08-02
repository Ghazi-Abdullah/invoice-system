{{-- resources/views/emails/support/new-ticket.blade.php --}}
<h1>تذكرة دعم جديدة</h1>
<p>رقم التذكرة: {{ $ticket->ticket_number }}</p>
<p>العميل: {{ $ticket->name }}</p>
<p>الموضوع: {{ $ticket->subject }}</p>
<p>الرسالة: {{ $ticket->message }}</p>