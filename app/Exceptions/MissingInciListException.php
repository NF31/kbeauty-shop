<?php

namespace App\Exceptions;

use App\Observers\ProductObserver;
use RuntimeException;

/** Levée par {@see ProductObserver::saving()}. */
class MissingInciListException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Un produit ne peut pas être publié sans sa liste INCI (ingredients_inci) — obligation légale cosmétique.');
    }
}
