<!DOCTYPE html>
<html dir="rtl" lang="fa">

<head>
    <title>تایید ایمیل</title>
    <meta charset="UTF-8">
    <style>
        @font-face {
            font-family: IRANSans;
            src: url('/fonts/IRANSans.ttf') format('truetype');
        }
    </style>
</head>

<body style="background-color: white; font-family: 'IRANSans', sans-serif; direction: rtl; text-align: right;">
    <h1 style="color: #3730a3;">تایید حساب کاربری</h1>
    <h3 style="color: #333333;">سلام، {{ $name }}!</h3>
    <p style="color: #333333;">لطفا برای تایید آدرس ایمیل خود روی دکمه زیر کلیک کنید:</p>
    <a href="{{ $verificationUrl }}"
        style="background-color: #3730a3; color: white; padding: 14px 25px; text-decoration: none; display: inline-block; border-radius: 5px;">
        تایید آدرس ایمیل
    </a>
    <p style="color: #333333;">این لینک فقط برای 1 ساعت آینده معتبر است.</p>
    <p style="color: #333333;">اگر شما حساب کاربری ایجاد نکرده‌اید، نیازی به انجام کار دیگری نیست.</p>
    <p style="color: #333333;">اگر شما این درخواست را نداده‌اید، لطفاً فوراً با پشتیبانی تماس بگیرید.</p>
    <p style="color: #333333;">باتشکر،</p>
    <p style="color: #333333;">تیم دنگ دان</p>
</body>

</html>
