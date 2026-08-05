<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تذكير بموعد استحقاق الفاتورة</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f5f7; font-family: Tahoma, Arial, sans-serif; direction: rtl;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td style="background-color:#2563eb; padding:20px 32px;">
                            <h1 style="margin:0; color:#ffffff; font-size:18px;">تذكير باستحقاق فاتورة</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px; color:#1f2937; font-size:14px; line-height:1.8;">
                            <p style="margin:0 0 16px;">مرحباً {{ $invoice->client->name }}،</p>
                            <p style="margin:0 0 16px;">نود تذكيرك بأن الفاتورة التالية تستحق السداد قريباً:</p>

                            <table role="presentation" width="100%" cellpadding="8" cellspacing="0" style="background-color:#f9fafb; border-radius:6px; margin:0 0 16px;">
                                <tr>
                                    <td style="color:#6b7280;">رقم الفاتورة</td>
                                    <td style="font-weight:bold;">{{ $invoice->invoice_number }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#6b7280;">تاريخ الاستحقاق</td>
                                    <td style="font-weight:bold;">{{ $invoice->due_date->format('Y-m-d') }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#6b7280;">المبلغ المستحق</td>
                                    <td style="font-weight:bold;">{{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</td>
                                </tr>
                            </table>

                            <p style="margin:0 0 24px;">نرجو التكرم بسداد المبلغ قبل تاريخ الاستحقاق لتجنب أي تأخير.</p>

                            <p style="margin:0; color:#6b7280; font-size:12px;">هذه رسالة تلقائية، الرجاء عدم الرد عليها مباشرة.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>