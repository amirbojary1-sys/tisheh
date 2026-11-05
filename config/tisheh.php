<?php

return ['trusted_proxies' => env('TRUSTED_PROXIES') ? explode(',', env('TRUSTED_PROXIES')) : null, 'frame_ancestors' => env('CSP_FRAME_ANCESTORS', "'self'"), 'payments_enabled' => false, 'wallet_enabled' => false, 'sms_enabled' => false, 'units' => ['bag' => 'پاکت', 'ton' => 'تن', 'piece' => 'عدد', 'meter' => 'متر', 'cubic_meter' => 'متر مکعب']];
