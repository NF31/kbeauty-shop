<?php

namespace App\Application\Returns\UseCases;

use App\Application\Orders\UseCases\RefundOrder;
use App\Enums\ReturnRequestStatus;
use App\Models\ReturnRequest;
use App\Notifications\ReturnRequestStatusUpdated;

/**
 * Acceptation d'une demande de retour (26.2) : déclenche directement le
 * remboursement Stripe du montant des lignes retournées via RefundOrder
 * (déjà idempotent/partiel-aware, 16.3) — décision confirmée avec
 * l'utilisateur plutôt que de séparer acceptation et remboursement en deux
 * actions manuelles. RefundOrder envoie sa propre notification
 * (RefundConfirmation) une fois le remboursement Stripe confirmé ;
 * ReturnRequestStatusUpdated ci-dessous couvre le statut de la *demande*
 * elle-même, une préoccupation distincte.
 */
class AcceptReturnRequest
{
    public function __construct(private readonly RefundOrder $refundOrder) {}

    public function __invoke(ReturnRequest $returnRequest): ReturnRequest
    {
        $returnRequest->load('items');

        ($this->refundOrder)(
            $returnRequest->order,
            $returnRequest->totalAmountCents(),
            "Retour accepté (demande #{$returnRequest->id})",
        );

        $returnRequest->update([
            'status' => ReturnRequestStatus::Accepted,
            'decided_at' => now(),
        ]);

        $returnRequest->user->notify(new ReturnRequestStatusUpdated($returnRequest));

        return $returnRequest;
    }
}
