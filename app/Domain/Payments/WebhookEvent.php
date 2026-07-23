<?php

namespace App\Domain\Payments;

final readonly class WebhookEvent
{
    public function __construct(
        public string $type,
        /** Null pour un type d'event qui ne porte pas de PaymentIntent. */
        public ?string $paymentIntentId,
    ) {}
}
