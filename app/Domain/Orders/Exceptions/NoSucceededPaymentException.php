<?php

namespace App\Domain\Orders\Exceptions;

use RuntimeException;

class NoSucceededPaymentException extends RuntimeException
{
    public static function forOrder(): self
    {
        return new self('Aucun paiement réussi à rembourser pour cette commande.');
    }
}
