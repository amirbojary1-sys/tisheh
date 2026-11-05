<?php

namespace App\Data;

final readonly class GatewayVerification
{
    public function __construct(public string $status, public string $reference, public bool $isDemo) {}
}
