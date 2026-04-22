<!DOCTYPE html>
<html dir="rtl" lang="ar">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
        }

        .container {
            max-width: 500px;
            margin: 40px auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
        }

        .otp-box {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 10px;
            color: #2563eb;
            text-align: center;
            padding: 20px;
            background: #eff6ff;
            border-radius: 8px;
            margin: 20px 0;
        }

        .footer {
            color: #888;
            font-size: 12px;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2 style="text-align:center;">رمز التحقق</h2>
        <p>مرحباً، رمز التحقق الخاص بك هو:</p>
        <div class="otp-box">{{ $otp }}</div>
        <p>⏰ الرمز صالح لمدة <strong>10 دقائق</strong> فقط.</p>
        <p>إذا لم تطلب هذا الرمز، تجاهل هذه الرسالة.</p>
        <div class="footer">Invoice System &copy; {{ date('Y') }}</div>
    </div>
</body>

</html>
