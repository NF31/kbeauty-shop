<?php

namespace App\Enums;

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
