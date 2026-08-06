<?php

namespace App\Application\Orders\UseCases;

use App\Domain\Orders\Contracts\PaymentRepositoryInterface;
use App\Domain\Payments\CheckoutPaymentResult;
use App\Domain\Payments\CheckoutSessionResult;
use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Order;

/**
 * Étape "Payer" du récapitulatif (docs/ARCHITECTURE.md §4) : crée (ou
 * réutilise) la `Checkout Session` Stripe correspondant à la commande. La
 * confirmation définitive du paiement n'arrive jamais ici mais via le
 * webhook Stripe (tâche 9.4, voir ConfirmOrderPayment).
 *
 * Tant qu'un `Payment` est `pending`, `provider_payment_id` stocke l'id de
 * la Checkout Session (jamais un PaymentIntent — celui-ci n'existe pas
 * encore côté Stripe à ce stade, cf. StripePaymentGateway::createCheckoutSession).
 */
class ProcessCheckoutPayment
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly PaymentRepositoryInterface $payments,
    ) {}

    public function __invoke(Order $order, string $returnUrl): CheckoutPaymentResult
    {
        $payment = $this->payments->findLatestPending($order);

        if ($payment) {
            // Le statut local reste `pending` tant que le webhook (9.4) n'a
            // pas confirmé le paiement — mais côté Stripe la Checkout Session
            // peut déjà être payée (ex. rechargement de la page après un
            // paiement réussi).
            $session = $this->gateway->retrieveCheckoutSession($payment->provider_payment_id);

            if ($session->paymentStatus === 'paid') {
                return CheckoutPaymentResult::alreadySucceeded();
            }

            // Réutilise la session existante tant qu'elle est encore valide
            // (`open`) et pour le même montant : recréer une session à
            // chaque rechargement de page invaliderait le formulaire de
            // paiement déjà affiché côté client — si le client confirmait
            // alors ce paiement, son webhook ne retrouverait plus aucun
            // `Payment` (provider_payment_id déjà remplacé), cf. incident du
            // 2026-08-06. Une Checkout Session ne pouvant pas être mise à
            // jour en place (contrairement à l'ancien PaymentIntent), une
            // nouvelle session n'est créée que si l'ancienne a expiré ou si
            // le montant de la commande a changé entre deux passages.
            if ($session->status === 'open' && $payment->amount_cents === $order->total_cents) {
                return CheckoutPaymentResult::pending(new CheckoutSessionResult(
                    id: $session->id,
                    clientSecret: $session->clientSecret,
                ));
            }

            $newSession = $this->gateway->createCheckoutSession($order, $returnUrl);

            $this->payments->replaceProviderPaymentId($payment, $newSession->id, $order->total_cents);

            return CheckoutPaymentResult::pending($newSession);
        }

        $session = $this->gateway->createCheckoutSession($order, $returnUrl);

        $this->payments->create([
            'order_id' => $order->id,
            'provider' => PaymentProvider::Stripe,
            'provider_payment_id' => $session->id,
            'status' => PaymentStatus::Pending,
            'amount_cents' => $order->total_cents,
        ]);

        return CheckoutPaymentResult::pending($session);
    }
}
