<?php

namespace App\Domain\Payments;

/**
 * Résultat d'une demande de remboursement Stripe : `status` reflète l'état
 * du `Refund` côté Stripe (ex. `succeeded`, `pending`), pas le statut local
 * `RefundStatus` du modèle applicatif.
 */
final readonly class RefundResult
{
    public function __construct(
        public string $id,
        public string $status,
    ) {}
}
