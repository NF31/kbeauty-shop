<?php

namespace App\Application\Orders\UseCases;

use App\Domain\Orders\Contracts\PaymentRepositoryInterface;
use App\Domain\Payments\CheckoutPaymentResult;
use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Order;

/**
 * Étape "Payer" du récapitulatif (docs/ARCHITECTURE.md §4) : crée (ou
 * remplace) la `Checkout Session` Stripe correspondant à la commande. La
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

            // Contrairement à l'ancien PaymentIntent, une Checkout Session ne
            // peut pas être mise à jour en place (ex. montant modifié entre
            // deux passages) — on en crée toujours une nouvelle tant que le
            // paiement précédent n'a pas réussi, et on repointe la ligne
            // Payment existante dessus.
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
