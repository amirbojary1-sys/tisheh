<?php

namespace App\Services\Integrations;

use App\Contracts\OtpSender;
use App\Support\DemoMode;

class DemoOtpSender implements OtpSender
{
    public function send(string $mobile, string $code): void
    {
        DemoMode::ensureOtpEnabled();
        if (! in_array($mobile, config('integrations.otp_demo_mobiles'), true)) {
            throw new \LogicException('Demo OTP is restricted to configured test accounts.');
        }
        // No SMS and no OTP logging. The controller presents the demo code once.
    }
}
