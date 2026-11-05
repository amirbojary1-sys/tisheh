<?php

namespace App\Services\Integrations;

use App\Contracts\PaymentGateway;
use App\Data\GatewayVerification;

/** Extension point only. NOT a working Mellat adapter. See docs/DEMO-INTEGRATIONS.fa.md. */
class MellatGateway implements PaymentGateway
{
    public function create(int $amount, string $currency, string $orderReference): string
    {
        throw new \LogicException('Real Mellat integration is not implemented.');
    }

    public function verify(string $providerReference, int $expectedAmount, string $currency, array $callbackData): GatewayVerification
    {
        throw new \LogicException('Implement authoritative server-side verify and settlement with the selected PSP.');
    }
}
