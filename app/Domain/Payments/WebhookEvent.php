<?php

namespace App\Domain\Payments;

final readonly class WebhookEvent
{
    public function __construct(
        public string $type,
        /** Id de la Checkout Session (`data.object.id`) — null pour un type d'event qui n'en porte pas. */
        public ?string $sessionId,
        /** Id du PaymentIntent sous-jacent — null tant que le client n'a pas confirmé le paiement. */
        public ?string $paymentIntentId,
        /** Valeur Stripe brute (`paid`, `unpaid`, `no_payment_required`) — null pour un type d'event qui n'en porte pas. */
        public ?string $paymentStatus,
    ) {}
}
