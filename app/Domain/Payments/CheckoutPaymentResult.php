<?php

namespace App\Domain\Payments;

final readonly class CheckoutPaymentResult
{
    private function __construct(
        public bool $alreadySucceeded,
        public ?CheckoutSessionResult $session,
    ) {}

    public static function alreadySucceeded(): self
    {
        return new self(true, null);
    }

    public static function pending(CheckoutSessionResult $session): self
    {
        return new self(false, $session);
    }
}
