<?php

namespace App\Domain\Payments;

final readonly class CheckoutPaymentResult
{
    private function __construct(
        public bool $alreadySucceeded,
        public ?PaymentIntentResult $intent,
    ) {}

    public static function alreadySucceeded(): self
    {
        return new self(true, null);
    }

    public static function pending(PaymentIntentResult $intent): self
    {
        return new self(false, $intent);
    }
}
