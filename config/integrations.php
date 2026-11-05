<?php

return [
    'demo_enabled' => (bool) env('TISHEH_DEMO_ENABLED', false),
    'otp_demo_enabled' => (bool) env('TISHEH_DEMO_OTP_ENABLED', false),
    'otp_demo_mobiles' => array_filter(explode(',', env('DEMO_OTP_MOBILES', '09000000002,09000000003,09000000004'))),
    'otp_ttl_seconds' => 120, 'otp_max_attempts' => 5, 'payment_ttl_minutes' => 15,
    // Reserved configuration only: no real Mellat connection is implemented.
    'mellat' => ['terminal_id' => env('MELLAT_TERMINAL_ID'), 'username' => env('MELLAT_USERNAME'), 'password' => env('MELLAT_PASSWORD')],
];
