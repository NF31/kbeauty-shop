<?php

namespace App\Infrastructure\Rabbitmq;

use JsonSchema\Validator;
use RuntimeException;
use stdClass;

/**
 * Valide un evenement diagnostic.created recu depuis RabbitMQ contre
 * contracts/rabbitmq/diagnostic-created.schema.json - meme contrat que
 * cote kbeauty-ai-core-service (producteur), voir contracts/README.md.
 */
class DiagnosticCreatedEventValidator
{
    private readonly object $schema;

    public function __construct(?string $schemaPath = null)
    {
        $path = $schemaPath ?? base_path('contracts/rabbitmq/diagnostic-created.schema.json');

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("Schema introuvable : {$path}");
        }

        $this->schema = json_decode($contents);
    }

    /**
     * @throws InvalidDiagnosticCreatedEventException si $rawJson ne respecte pas le contrat
     */
    public function validate(string $rawJson): stdClass
    {
        $data = json_decode($rawJson);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidDiagnosticCreatedEventException(['payload JSON invalide : '.json_last_error_msg()]);
        }

        $validator = new Validator;
        $validator->validate($data, $this->schema);

        if (! $validator->isValid()) {
            $errors = array_map(
                static fn (array $error) => "{$error['property']}: {$error['message']}",
                $validator->getErrors()
            );

            throw new InvalidDiagnosticCreatedEventException($errors);
        }

        return $data;
    }
}
