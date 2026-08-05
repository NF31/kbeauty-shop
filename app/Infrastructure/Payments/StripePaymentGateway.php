<?php

namespace App\Infrastructure\Payments;

use App\Domain\Payments\CheckoutSessionResult;
use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\PaymentIntentResult;
use App\Domain\Payments\RefundResult;
use App\Domain\Payments\WebhookEvent;
use App\Models\Order;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripePaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly StripeClient $stripe) {}

    /**
     * Crée une `Checkout Session` Stripe (`ui_mode: elements`, docs/ARCHITECTURE.md
     * §4) pour le montant total de la commande — un seul poste de facturation,
     * Stripe ne voit jamais le détail du panier. `ui_mode: elements` garde le
     * `PaymentElement` intégré sur la page du site (pas de redirection vers une
     * page Stripe hébergée). Le tunnel n'accepte pas les invités
     * (`checkout.auth`), `$order->user` est donc toujours défini.
     */
    public function createCheckoutSession(Order $order, string $returnUrl): CheckoutSessionResult
    {
        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'ui_mode' => 'elements',
            'return_url' => $returnUrl,
            'locale' => app()->getLocale() === 'en' ? 'en' : 'fr',
            'customer_email' => $order->user?->email,
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($order->currency),
                    'product_data' => ['name' => "Commande {$order->order_number}"],
                    'unit_amount' => $order->total_cents,
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
            ],
        ]);

        return new CheckoutSessionResult(
            id: $session->id,
            clientSecret: $session->client_secret,
            paymentIntentId: $session->payment_intent,
        );
    }

    /**
     * Une `Checkout Session` en mode `payment` crée automatiquement un
     * `PaymentIntent` sous-jacent (`session.payment_intent`) — c'est son id
     * qui est stocké dans `payments.provider_payment_id`, jamais l'id de la
     * session elle-même. Utilisé pour vérifier si un paiement est déjà
     * confirmé (rechargement de la page, reprise de paiement 9.7) et par le
     * webhook (`payment_intent.succeeded`).
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntentResult
    {
        return $this->toPaymentIntentResult($this->stripe->paymentIntents->retrieve($paymentIntentId));
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
     * traiter un payload de webhook sans cette vérification. Le `\Stripe\Event`
     * du SDK est converti en DTO ici : c'est la seule classe du projet qui a
     * besoin de connaître sa forme, tout le reste du code métier ne dépend que
     * de `WebhookEvent`.
     *
     * @throws SignatureVerificationException si la signature est invalide/absente.
     */
    public function verifyWebhookSignature(string $payload, string $signature): WebhookEvent
    {
        $event = Webhook::constructEvent($payload, $signature, config('services.stripe.webhook_secret'));

        return new WebhookEvent(
            type: $event->type,
            paymentIntentId: $event->data->object->id ?? null,
        );
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
