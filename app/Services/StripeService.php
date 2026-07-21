<?php

namespace App\Services;

use App\Models\Order;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeService
{
    public function __construct(private readonly StripeClient $stripe) {}

    /**
     * Crée un `PaymentIntent` Stripe pour le montant total de la commande.
     * `automatic_payment_methods` laisse Stripe proposer CB/Apple Pay/Google Pay
     * selon la configuration du dashboard (docs/ARCHITECTURE.md §4), sans lister
     * les méthodes une à une côté code.
     */
    public function createPaymentIntent(Order $order): PaymentIntent
    {
        return $this->stripe->paymentIntents->create([
            'amount' => $order->total_cents,
            'currency' => strtolower($order->currency),
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
            ],
        ]);
    }

    /**
     * Ré-utilisée quand une commande a déjà un `PaymentIntent` en attente
     * (rechargement de la page de paiement) plutôt que d'en recréer un.
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->stripe->paymentIntents->retrieve($paymentIntentId);
    }

    /**
     * Le montant peut changer entre la création du `PaymentIntent` et le
     * moment où le client paie (ex. modification du panier dans un autre
     * onglet) — on resynchronise plutôt que de faire confiance à un montant
     * potentiellement obsolète.
     */
    public function updatePaymentIntentAmount(string $paymentIntentId, int $amountCents): PaymentIntent
    {
        return $this->stripe->paymentIntents->update($paymentIntentId, [
            'amount' => $amountCents,
        ]);
    }

    /**
     * Vérifie que la requête de webhook provient bien de Stripe (signature
     * `Stripe-Signature`) avant de faire confiance à son contenu — jamais
     * traiter un payload de webhook sans cette vérification.
     *
     * @throws SignatureVerificationException si la signature est invalide/absente.
     */
    public function constructWebhookEvent(string $payload, string $signature): Event
    {
        return Webhook::constructEvent($payload, $signature, config('services.stripe.webhook_secret'));
    }
}
