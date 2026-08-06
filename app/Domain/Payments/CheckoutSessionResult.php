<?php

namespace App\Domain\Payments;

/**
 * Identifiants d'une Stripe Checkout Session nécessaires au formulaire de
 * paiement côté client (`clientSecret` absent si la session est déjà payée
 * ou a expiré).
 */
final readonly class CheckoutSessionResult
{
    public function __construct(
        public string $id,
        public ?string $clientSecret,
    ) {}
}
