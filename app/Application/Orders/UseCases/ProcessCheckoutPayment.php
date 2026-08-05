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
            // pas confirmé le paiement — mais côté Stripe le PaymentIntent
            // sous-jacent peut déjà être `succeeded` (ex. rechargement de la
            // page après un paiement réussi).
            $intent = $this->gateway->retrievePaymentIntent($payment->provider_payment_id);

            if ($intent->status === 'succeeded') {
                return CheckoutPaymentResult::alreadySucceeded();
            }

            // Contrairement à l'ancien PaymentIntent, une Checkout Session ne
            // peut pas être mise à jour en place (ex. montant modifié entre
            // deux passages) — on en crée toujours une nouvelle tant que le
            // paiement précédent n'a pas réussi, et on repointe la ligne
            // Payment existante dessus.
            $session = $this->gateway->createCheckoutSession($order, $returnUrl);

            $this->payments->replaceProviderPaymentId($payment, $session->paymentIntentId, $order->total_cents);

            return CheckoutPaymentResult::pending($session);
        }

        $session = $this->gateway->createCheckoutSession($order, $returnUrl);

        $this->payments->create([
            'order_id' => $order->id,
            'provider' => PaymentProvider::Stripe,
            'provider_payment_id' => $session->paymentIntentId,
            'status' => PaymentStatus::Pending,
            'amount_cents' => $order->total_cents,
        ]);

        return CheckoutPaymentResult::pending($session);
    }
}
