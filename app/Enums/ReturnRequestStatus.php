<?php

namespace App\Enums;

use App\Http\Controllers\Admin\ReturnRequestController;

/** `Submitted` est le seul statut depuis lequel `accept()`/`refuse()` sont autorisés (garde dans {@see ReturnRequestController}). */
enum ReturnRequestStatus: string
{
    case Submitted = 'submitted';
    case Accepted = 'accepted';
    case Refused = 'refused';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Soumise',
            self::Accepted => 'Acceptée',
            self::Refused => 'Refusée',
        };
    }
}
