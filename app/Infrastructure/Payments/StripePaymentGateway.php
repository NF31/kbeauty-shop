<?php

namespace App\Infrastructure\Payments;

use App\Domain\Payments\CheckoutSessionResult;
use App\Domain\Payments\CheckoutSessionStatusResult;
use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\RefundResult;
use App\Domain\Payments\WebhookEvent;
use App\Models\Order;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\SignatureVerificationException;
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
     *
     * Le PaymentIntent sous-jacent (`session.payment_intent`) n'est PAS créé
     * synchroniquement pour `ui_mode: elements` — il n'existe qu'une fois le
     * client confirmé côté navigateur (incident du 2026-08-05, `NULL` renvoyé
     * ici en prod). C'est pourquoi seul l'id de session est renvoyé ici ; le
     * suivi du statut de paiement se fait via `retrieveCheckoutSession()`.
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
        );
    }

    /**
     * Utilisé tant qu'un `Payment` est encore `pending` : `provider_payment_id`
     * stocke alors l'id de la Checkout Session (jamais un PaymentIntent, cf.
     * `createCheckoutSession()`). Vérifie si le paiement est déjà passé côté
     * Stripe (ex. rechargement de la page après un paiement réussi) sans
     * attendre le webhook.
     *
     * `$sessionId` peut être un id hérité d'avant la migration Checkout
     * Sessions (2026-08-06) : un `Payment` `pending` créé sous l'ancien flux
     * PaymentIntent stocke un id `pi_...`, pas `cs_...` — Stripe répond alors
     * "No such checkout.session" (incident constaté en prod juste après la
     * migration). Traité comme "pas encore payé" plutôt que de faire planter
     * le paiement : `ProcessCheckoutPayment` crée une nouvelle session et
     * remplace cet id invalide, auto-guérissant l'état.
     */
    public function retrieveCheckoutSession(string $sessionId): CheckoutSessionStatusResult
    {
        try {
            $session = $this->stripe->checkout->sessions->retrieve($sessionId);
        } catch (InvalidRequestException) {
            return new CheckoutSessionStatusResult(
                id: $sessionId,
                paymentStatus: 'unpaid',
                paymentIntentId: null,
                status: 'expired',
                clientSecret: null,
            );
        }

        return new CheckoutSessionStatusResult(
            id: $session->id,
            paymentStatus: $session->payment_status,
            paymentIntentId: $session->payment_intent,
            status: $session->status,
            clientSecret: $session->client_secret,
        );
    }

    /**
     * Rembourse tout ou partie d'un paiement déjà capturé. `amountCents` est
     * toujours fourni explicitement (jamais le montant total du `PaymentIntent`
     * par défaut) pour supporter aussi bien un remboursement partiel que total.
     * `$paymentIntentId` doit être un vrai id de PaymentIntent — vrai une fois
     * `ConfirmOrderPayment` passé, cf. `EloquentPaymentRepository::markSucceeded()`.
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
     * L'objet porté par l'event est une Checkout Session pour
     * `checkout.session.completed`/`checkout.session.async_payment_succeeded`
     * (ceux réellement traités, cf. StripeWebhookController) : `id` est l'id
     * de session, `payment_intent`/`payment_status` sont ses propriétés
     * natives.
     *
     * @throws SignatureVerificationException si la signature est invalide/absente.
     */
    public function verifyWebhookSignature(string $payload, string $signature): WebhookEvent
    {
        $event = Webhook::constructEvent($payload, $signature, config('services.stripe.webhook_secret'));
        $object = $event->data->object;

        return new WebhookEvent(
            type: $event->type,
            sessionId: $object->id ?? null,
            paymentIntentId: $object->payment_intent ?? null,
            paymentStatus: $object->payment_status ?? null,
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
