<?php

namespace App\Domain\Payments;

final readonly class CheckoutSessionStatusResult
{
    public function __construct(
        public string $id,
        /** Valeur Stripe brute : `paid`, `unpaid` ou `no_payment_required`. */
        public string $paymentStatus,
        /**
         * Null tant que le client n'a pas confirmé le paiement — le
         * PaymentIntent d'une Checkout Session (`ui_mode: elements`) n'est
         * créé que lors de la confirmation côté client, jamais à la création
         * de la session (docs/ARCHITECTURE.md §4, incident du 2026-08-05).
         */
        public ?string $paymentIntentId,
    ) {}
}
