<!DOCTYPE html>
<html dir="rtl" lang="fa">

<head>
    <title>تغییر رمز عبور</title>
    <meta charset="UTF-8">
    <style>
        @font-face {
            font-family: IRANSans;
            src: url('/fonts/IRANSans.ttf') format('truetype');
        }
    </style>
</head>

<body style="background-color: white; font-family: 'IRANSans', sans-serif; direction: rtl; text-align: right;">
    <h1 style="color: #3730a3;">تغییر رمز عبور</h1>
    <h3 style="color: #333333;">سلام، {{ $name }}!</h3>
    <p style="color: #333333;">در تاریخ
        {{ \Morilog\Jalali\Jalalian::fromDateTime(now()->setTimezone('Asia/Tehran'))->format('Y/m/d') }} و ساعت
        {{ \Morilog\Jalali\Jalalian::fromDateTime(now()->setTimezone('Asia/Tehran'))->format('H:i') }} رمز عبور حساب
        کاربری شما با موفقیت تغییر کرد.</p>
    <p style="color: #333333;">اگر شما این تغییر را انجام نداده‌اید، لطفاً سریعاً با پشتیبانی تماس بگیرید.</p>
    <p style="color: #333333;">باتشکر،</p>
    <p style="color: #333333;">تیم دنگ دان</p>
</body>

</html>
