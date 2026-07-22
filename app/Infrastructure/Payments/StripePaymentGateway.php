<?php

namespace App\Infrastructure\Payments;

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\PaymentIntentResult;
use App\Domain\Payments\RefundResult;
use App\Models\Order;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripePaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly StripeClient $stripe) {}

    /**
     * Crée un `PaymentIntent` Stripe pour le montant total de la commande.
     * `automatic_payment_methods` laisse Stripe proposer CB/Apple Pay/Google Pay
     * selon la configuration du dashboard (docs/ARCHITECTURE.md §4), sans lister
     * les méthodes une à une côté code.
     */
    public function createPaymentIntent(Order $order): PaymentIntentResult
    {
        $intent = $this->stripe->paymentIntents->create([
            'amount' => $order->total_cents,
            'currency' => strtolower($order->currency),
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
            ],
        ]);

        return $this->toPaymentIntentResult($intent);
    }

    /**
     * Ré-utilisée quand une commande a déjà un `PaymentIntent` en attente
     * (rechargement de la page de paiement) plutôt que d'en recréer un.
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntentResult
    {
        return $this->toPaymentIntentResult($this->stripe->paymentIntents->retrieve($paymentIntentId));
    }

    /**
     * Le montant peut changer entre la création du `PaymentIntent` et le
     * moment où le client paie (ex. modification du panier dans un autre
     * onglet) — on resynchronise plutôt que de faire confiance à un montant
     * potentiellement obsolète.
     */
    public function updatePaymentIntentAmount(string $paymentIntentId, int $amountCents): PaymentIntentResult
    {
        $intent = $this->stripe->paymentIntents->update($paymentIntentId, [
            'amount' => $amountCents,
        ]);

        return $this->toPaymentIntentResult($intent);
    }

    /**
     * Rembourse tout ou partie d'un paiement déjà capturé. `amountCents` est
     * toujours fourni explicitement (jamais le montant total du `PaymentIntent`
     * par défaut) pour supporter aussi bien un remboursement partiel que total.
     */
    public function refund(string $paymentIntentId, int $amountCents): RefundResult
    {
        $refund = $this->stripe->refunds->create([
            'payment_intent' => $paymentIntentId,
            'amount' => $amountCents,
        ]);

        return $this->toRefundResult($refund);
    }

    /**
     * Vérifie que la requête de webhook provient bien de Stripe (signature
     * `Stripe-Signature`) avant de faire confiance à son contenu — jamais
     * traiter un payload de webhook sans cette vérification.
     *
     * @throws SignatureVerificationException si la signature est invalide/absente.
     */
    public function verifyWebhookSignature(string $payload, string $signature): Event
    {
        return Webhook::constructEvent($payload, $signature, config('services.stripe.webhook_secret'));
    }

    private function toPaymentIntentResult(PaymentIntent $intent): PaymentIntentResult
    {
        return new PaymentIntentResult(
            id: $intent->id,
            clientSecret: $intent->client_secret,
            status: $intent->status,
        );
    }

    private function toRefundResult(Refund $refund): RefundResult
    {
        return new RefundResult(
            id: $refund->id,
            status: $refund->status,
        );
    }
}
