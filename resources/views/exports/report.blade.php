<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير {{ $reportType }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 24px;
        }

        .info-section {
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .info-label {
            font-weight: bold;
            color: #555;
        }

        .info-value {
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background-color: #2c3e50;
            color: white;
            padding: 10px;
            text-align: right;
            font-weight: bold;
        }

        td {
            padding: 8px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .total-row {
            font-weight: bold;
            background-color: #e8f4f8 !important;
        }

        .date {
            color: #666;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>تقرير {{ $reportType }}</h1>
        <div class="date">تاريخ التقرير: {{ now()->format('Y-m-d H:i') }}</div>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">المستخدم:</span>
            <span class="info-value">{{ $user->name ?? 'غير معروف' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">تاريخ البدء:</span>
            <span class="info-value">{{ $filters['start_date'] ?? 'غير محدد' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">تاريخ الانتهاء:</span>
            <span class="info-value">{{ $filters['end_date'] ?? 'غير محدد' }}</span>
        </div>
    </div>

    @if($reportType === 'invoices')
        <table>
            <thead>
                <tr>
                    <th>رقم الفاتورة</th>
                    <th>العميل</th>
                    <th>تاريخ الإصدار</th>
                    <th>تاريخ الاستحقاق</th>
                    <th>المجموع</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($data['invoices']['data'] ?? $data['invoices'] ?? []) as $invoice)
                <tr>
                    <td>{{ $invoice['invoice_number'] ?? '' }}</td>
                    <td>{{ $invoice['client']['name'] ?? '' }}</td>
                    <td>{{ $invoice['issue_date'] ?? '' }}</td>
                    <td>{{ $invoice['due_date'] ?? '' }}</td>
                    <td>{{ number_format($invoice['total_amount'] ?? 0, 2) }}</td>
                    <td>{{ $invoice['status'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if(isset($data['summary']))
        <div class="info-section" style="margin-top: 20px;">
            <div class="info-row">
                <span class="info-label">إجمالي الفواتير:</span>
                <span class="info-value">{{ $data['summary']['total_invoices'] ?? 0 }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">إجمالي المبلغ:</span>
                <span class="info-value">{{ number_format($data['summary']['total_amount'] ?? 0, 2) }}</span>
            </div>
        </div>
        @endif

    @elseif($reportType === 'clients')
        <table>
            <thead>
                <tr>
                    <th>اسم العميل</th>
                    <th>البريد الإلكتروني</th>
                    <th>عدد الفواتير</th>
                    <th>إجمالي المبلغ</th>
                    <th>المبلغ المدفوع</th>
                    <th>المبلغ المتبقي</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($data['clients'] ?? []) as $client)
                <tr>
                    <td>{{ $client['client_name'] ?? '' }}</td>
                    <td>{{ $client['client_email'] ?? '' }}</td>
                    <td>{{ $client['total_invoices'] ?? 0 }}</td>
                    <td>{{ number_format($client['total_amount'] ?? 0, 2) }}</td>
                    <td>{{ number_format($client['total_paid'] ?? 0, 2) }}</td>
                    <td>{{ number_format($client['total_due'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

    @elseif($reportType === 'revenue')
        <table>
            <thead>
                <tr>
                    <th>الشهر</th>
                    <th>عدد الفواتير</th>
                    <th>الإجمالي</th>
                    <th>المتحصلات</th>
                    <th>المستحقات</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($data['revenue_data'] ?? []) as $revenue)
                <tr>
                    <td>{{ $revenue['month'] ?? '' }}</td>
                    <td>{{ $revenue['count'] ?? 0 }}</td>
                    <td>{{ number_format($revenue['total'] ?? 0, 2) }}</td>
                    <td>{{ number_format($revenue['paid'] ?? 0, 2) }}</td>
                    <td>{{ number_format($revenue['due'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

    @elseif($reportType === 'overdue')
        <table>
            <thead>
                <tr>
                    <th>رقم الفاتورة</th>
                    <th>العميل</th>
                    <th>تاريخ الإصدار</th>
                    <th>تاريخ الاستحقاق</th>
                    <th>أيام التأخير</th>
                    <th>المبلغ</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($data['overdue_invoices'] ?? []) as $invoice)
                <tr>
                    <td>{{ $invoice['invoice_number'] ?? '' }}</td>
                    <td>{{ $invoice['client_name'] ?? '' }}</td>
                    <td>{{ $invoice['issue_date'] ?? '' }}</td>
                    <td>{{ $invoice['due_date'] ?? '' }}</td>
                    <td>{{ $invoice['days_overdue'] ?? 0 }}</td>
                    <td>{{ number_format($invoice['total_amount'] ?? 0, 2) }}</td>
                    <td>{{ $invoice['status'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        تم إنشاء هذا التقرير تلقائياً بواسطة نظام الفواتير<br>
        {{ config('app.name') }} - جميع الحقوق محفوظة &copy; {{ date('Y') }}
    </div>
</body>
</html>
