<!DOCTYPE html>
<html dir="rtl" lang="fa">

<head>
    <title>بازیابی رمز عبور</title>
    <meta charset="UTF-8">
    <style>
        @font-face {
            font-family: IRANSans;
            src: url('/fonts/IRANSans.ttf') format('truetype');
        }
    </style>
</head>

<body style="background-color: white; font-family: 'IRANSans', sans-serif; direction: rtl; text-align: right;">
    <h1 style="color: #3730a3;">بازیابی رمز عبور</h1>
    <h3 style="color: #333333;">سلام، {{ $name }}!</h3>
    <p style="color: #333333;">شما درخواست بازیابی رمز عبور خود را داده‌اید. برای تغییر رمز عبور روی دکمه زیر کلیک کنید:
    </p>
    <a href="{{ $url }}"
        style="background-color: #3730a3; color: white; padding: 14px 25px; text-decoration: none; display: inline-block; border-radius: 5px;">
        تغییر رمز عبور
    </a>
    <p style="color: #333333;">این لینک فقط برای 1 ساعت آینده معتبر است.</p>
    <p style="color: #333333;">اگر شما این درخواست را نداده‌اید، لطفاً این ایمیل را نادیده بگیرید.</p>
    <p style="color: #333333;">در صورت نیاز به کمک، با پشتیبانی تماس بگیرید.</p>
    <p style="color: #333333;">باتشکر،</p>
    <p style="color: #333333;">تیم دنگ دان</p>
</body>

</html>
