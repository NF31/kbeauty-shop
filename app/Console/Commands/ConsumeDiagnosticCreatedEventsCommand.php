<?php

namespace App\Console\Commands;

use App\Infrastructure\Rabbitmq\DiagnosticCreatedEventValidator;
use App\Infrastructure\Rabbitmq\InvalidDiagnosticCreatedEventException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Processus long-vivant (pas planifie via routes/console.php - a lancer en
 * supervisor/systemd en prod, comme `queue:work`). Consomme l'evenement
 * `diagnostic.created` publie par kbeauty-ai-core-service, valide chaque
 * message contre le contrat JSON Schema partage avant tout traitement.
 */
#[Signature('rabbitmq:consume-diagnostic-created')]
#[Description("Ecoute l'evenement diagnostic.created (RabbitMQ), valide chaque message contre le contrat JSON Schema partage avec kbeauty-ai-core-service")]
class ConsumeDiagnosticCreatedEventsCommand extends Command
{
    public function handle(DiagnosticCreatedEventValidator $validator): int
    {
        $config = config('rabbitmq');

        $connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            $config['vhost'],
        );
        $channel = $connection->channel();

        // Declaration idempotente : memes noms/durabilite que RabbitMQConfig.java
        // cote kbeauty-ai-core-service - permet de demarrer ce consommateur
        // meme si le service Spring Boot n'a pas encore tourne une premiere fois.
        $channel->exchange_declare($config['exchange'], 'direct', false, true, false);
        $channel->queue_declare($config['queue'], false, true, false, false);
        $channel->queue_bind($config['queue'], $config['exchange'], $config['routing_key']);

        $this->info("En ecoute sur la queue « {$config['queue']} »... (Ctrl+C pour arreter)");

        $channel->basic_consume(
            queue: $config['queue'],
            no_ack: false,
            callback: fn (AMQPMessage $message) => $this->handleMessage($message, $validator),
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

        return self::SUCCESS;
    }

    private function handleMessage(AMQPMessage $message, DiagnosticCreatedEventValidator $validator): void
    {
        try {
            $event = $validator->validate($message->getBody());
        } catch (InvalidDiagnosticCreatedEventException $e) {
            Log::warning('RabbitMQ diagnostic.created : message rejete (contrat non respecte)', [
                'error' => $e->getMessage(),
                'raw_body' => $message->getBody(),
            ]);

            // requeue: false - pas de re-livraison infinie d'un message qui ne
            // respectera jamais le contrat sans intervention humaine. Un vrai
            // dead-letter exchange serait l'etape suivante en prod, hors
            // scope pedagogique ici (voir docs/montee-competence/12-contrat-rabbitmq-laravel.md).
            $message->nack(requeue: false);

            return;
        }

        Log::info('RabbitMQ diagnostic.created : evenement recu et valide', [
            'diagnosticId' => $event->diagnosticId,
            'status' => $event->status,
            'occurredAt' => $event->occurredAt,
        ]);

        $message->ack();
    }
}
