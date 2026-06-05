<?php

namespace App\Support\Payments;

class PaymentInitialization
{
    public function __construct(
        public readonly string $provider,
        public readonly string $reference,
        public readonly string $authorizationUrl,
        public readonly array $payload = [],
    ) {}
}
