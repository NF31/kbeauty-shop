<?php

namespace App\Domain\Payments;

final readonly class CheckoutSessionResult
{
    public function __construct(
        public string $id,
        public ?string $clientSecret,
    ) {}
}
