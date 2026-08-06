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
        /**
         * Statut de la session elle-même (`open`/`complete`/`expired`) —
         * distinct de `paymentStatus`. Permet de savoir si la session est
         * encore réutilisable telle quelle (`open`) ou doit être recréée
         * (`expired`). `expired` sert aussi de sentinelle quand l'id stocké
         * n'était pas une Checkout Session valide (cf. `StripePaymentGateway`).
         */
        public string $status,
        /**
         * Le `client_secret` de cette même session, réutilisable tel quel
         * tant qu'elle est `open` et non payée — évite de recréer une
         * nouvelle session (et donc un nouveau `clientSecret`) à chaque
         * rechargement de la page pendant que le client a encore l'ancien
         * formulaire de paiement affiché (incident du 2026-08-06 : le
         * paiement confirmé sur l'ancienne session n'était plus rattaché à
         * aucun `Payment`, `provider_payment_id` ayant déjà été remplacé).
         */
        public ?string $clientSecret,
    ) {}
}
