<?php

namespace App\Contracts;

use App\Data\GatewayVerification;

interface PaymentGateway
{
    public function create(int $amount, string $currency, string $orderReference): string;

    public function verify(string $providerReference, int $expectedAmount, string $currency, array $callbackData): GatewayVerification;
}
