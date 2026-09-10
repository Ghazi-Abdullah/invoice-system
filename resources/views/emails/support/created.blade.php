<!DOCTYPE html>
<html dir="rtl" lang="ar">

<head>
    <meta charset="UTF-8">
    <title>تأكيد استلام التذكرة</title>
</head>

<body style="font-family: Arial, sans-serif; direction: rtl; text-align: right; padding: 20px;">

    <h2 style="color: #667eea;">مرحباً {{ $ticket->name }}،</h2>

    <p>تم استلام تذكرتك بنجاح وسنقوم بالرد عليك في أقرب وقت.</p>

    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <p><strong>رقم التذكرة:</strong> {{ $ticket->ticket_number }}</p>
        <p><strong>الموضوع:</strong> {{ $ticket->subject }}</p>
        <p><strong>الرسالة:</strong> {{ $ticket->message }}</p>
    </div>

    <p>يمكنك متابعة حالة التذكرة من خلال الرابط التالي:</p>
    <a href="{{ config('app.frontend_url') }}/support/track?ticket={{ $ticket->ticket_number }}"
        style="display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 6px;">
        متابعة التذكرة
    </a>

    <p style="color: #999; font-size: 12px; margin-top: 30px;">
        هذا إيميل تلقائي، يرجى عدم الرد عليه.
    </p>

</body>

</html>