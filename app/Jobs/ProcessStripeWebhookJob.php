<?php

namespace App\Jobs;

use App\Application\Orders\UseCases\ConfirmOrderPayment;
use App\Domain\Payments\WebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Models\WebhookCall;
use Throwable;

/**
 * Dispatché par StripeWebhookController une fois la signature vérifiée et
 * l'event identifié comme un paiement réussi. `$webhookCall` sert de trace
 * persistée (payload brut, exception éventuelle) ; le passage par la queue
 * donne le retry Laravel sur une erreur transitoire (DB down, deadlock...),
 * ce que l'ancien traitement synchrone dans la requête HTTP n'avait pas.
 */
class ProcessStripeWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(
        public readonly WebhookCall $webhookCall,
        public readonly WebhookEvent $event,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60, 300, 900];
    }

    public function handle(ConfirmOrderPayment $confirmOrderPayment): void
    {
        $eventId = $this->webhookCall->payload['id'] ?? null;

        if (is_string($eventId) && $this->alreadyProcessed($eventId)) {
            Log::info('Webhook Stripe : event déjà traité, ignoré.', ['event_id' => $eventId]);

            return;
        }

        if (! $this->event->sessionId || ! $this->event->paymentIntentId) {
            return;
        }

        try {
            $confirmOrderPayment($this->event->sessionId, $this->event->paymentIntentId);
        } catch (Throwable $e) {
            $this->webhookCall->update([
                'exception' => [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
            ]);

            throw $e;
        }
    }

    /**
     * Ne saute que sur un event déjà traité AVEC SUCCÈS (exception IS NULL) :
     * si une première tentative a échoué (job épuisé après ses retries), une
     * redélivraison Stripe du même event.id doit pouvoir retenter, pas être
     * ignorée silencieusement pour toujours.
     */
    private function alreadyProcessed(string $eventId): bool
    {
        return WebhookCall::query()
            ->where('id', '!=', $this->webhookCall->id)
            ->where('payload->id', $eventId)
            ->whereNull('exception')
            ->exists();
    }
}
