<?php

namespace App\Support;

class DemoMode
{
    public static function enabled(): bool
    {
        return app()->environment(['local', 'testing']) && config('integrations.demo_enabled') === true;
    }

    public static function otpEnabled(): bool
    {
        return app()->environment(['local', 'testing']) && config('integrations.otp_demo_enabled') === true;
    }

    public static function ensureEnabled(): void
    {
        abort_unless(self::enabled(), 503, 'پرداخت آزمایشی فقط با فعال‌سازی صریح در محیط توسعه در دسترس است.');
    }

    public static function ensureOtpEnabled(): void
    {
        abort_unless(self::otpEnabled(), 503, 'ورود OTP آزمایشی فقط با فعال‌سازی صریح در محیط توسعه در دسترس است.');
    }
}
