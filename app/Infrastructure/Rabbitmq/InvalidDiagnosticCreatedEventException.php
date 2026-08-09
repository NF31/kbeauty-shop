<?php

namespace App\Infrastructure\Rabbitmq;

use RuntimeException;

/** Levee quand un message recu ne respecte pas contracts/rabbitmq/diagnostic-created.schema.json. */
class InvalidDiagnosticCreatedEventException extends RuntimeException
{
    /** @param array<int, string> $errors */
    public function __construct(array $errors)
    {
        parent::__construct('Evenement invalide au regard du contrat JSON Schema : '.implode('; ', $errors));
    }
}
