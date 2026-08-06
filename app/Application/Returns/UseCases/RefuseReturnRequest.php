<?php

namespace App\Application\Returns\UseCases;

use App\Enums\ReturnRequestStatus;
use App\Models\ReturnRequest;
use App\Notifications\ReturnRequestStatusUpdated;

/**
 * Refus d'une demande de retour (26.2), pendant admin de
 * {@see AcceptReturnRequest} : contrairement
 * à l'acceptation, aucun remboursement n'est déclenché — juste le statut et
 * la notification client.
 */
class RefuseReturnRequest
{
    public function __invoke(ReturnRequest $returnRequest, ?string $adminNote): ReturnRequest
    {
        $returnRequest->update([
            'status' => ReturnRequestStatus::Refused,
            'admin_note' => $adminNote,
            'decided_at' => now(),
        ]);

        $returnRequest->user->notify(new ReturnRequestStatusUpdated($returnRequest));

        return $returnRequest;
    }
}
