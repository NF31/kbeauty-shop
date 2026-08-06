<?php

namespace App\Domain\Orders\Exceptions;

use RuntimeException;

/**
 * Levée quand un remboursement est demandé sur une commande dont aucun
 * `Payment` n'a le statut `Succeeded` (ex. paiement encore `pending`, ou
 * déjà `refunded`).
 */
class NoSucceededPaymentException extends RuntimeException
{
    public static function forOrder(): self
    {
        return new self('Aucun paiement réussi à rembourser pour cette commande.');
    }
}
