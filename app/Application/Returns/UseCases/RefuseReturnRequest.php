<?php

namespace App\Application\Returns\UseCases;

use App\Enums\ReturnRequestStatus;
use App\Models\ReturnRequest;
use App\Notifications\ReturnRequestStatusUpdated;

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
