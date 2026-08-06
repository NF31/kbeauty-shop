<?php

namespace App\Enums;

/**
 * Statut du `Payment` (pas de la commande — voir {@see OrderStatus}, ni du
 * remboursement Stripe — voir {@see RefundStatus}). `Succeeded`/`Refunded`
 * sont les deux états considérés "terminaux" par ConfirmOrderPayment pour
 * son idempotence.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Refunded = 'refunded';
}
