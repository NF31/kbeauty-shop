<?php

namespace App\Domain\Payments;

use App\Application\Orders\UseCases\ProcessCheckoutPayment;

/**
 * Résultat de {@see ProcessCheckoutPayment} :
 * soit le paiement est déjà confirmé côté Stripe (`alreadySucceeded`, session
 * rechargée après succès), soit une Checkout Session reste à afficher/payer
 * (`pending`, avec les infos nécessaires au formulaire côté client).
 */
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
