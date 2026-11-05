<?php

namespace App\Services\Integrations;

use App\Contracts\PaymentGateway;
use App\Data\GatewayVerification;
use App\Support\DemoMode;
use Illuminate\Support\Str;

class DemoGateway implements PaymentGateway
{
    public function create(int $amount, string $currency, string $orderReference): string
    {
        DemoMode::ensureEnabled();

        return 'DEMO-'.Str::uuid();
    }

    public function verify(string $providerReference, int $expectedAmount, string $currency, array $callbackData): GatewayVerification
    {
        DemoMode::ensureEnabled();
        $status = match ($callbackData['outcome'] ?? '') {
            'success' => 'demo_succeeded','failure' => 'demo_failed','cancel' => 'demo_cancelled',default => throw new \InvalidArgumentException('Invalid demo outcome')
        };

        return new GatewayVerification($status, $providerReference, true);
    }
}
