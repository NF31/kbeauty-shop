<?php

namespace App\Enums;

/** Statut du `Refund` Stripe lui-même — distinct de {@see PaymentStatus::Refunded}, qui reflète le `Payment` parent une fois le remboursement effectif. */
enum RefundStatus: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Succeeded => 'Effectué',
            self::Failed => 'Échoué',
        };
    }
}
